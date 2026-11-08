#!/usr/bin/env bash
# Run the tFPDF test suite on digest-pinned PHP 5.6.40 with verified PHPUnit 4.8.36 PHAR.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
USE_SUDO_DOCKER="${USE_SUDO_DOCKER:-0}"
PHPUNIT_VERSION="4.8.36"
PHPUNIT_SHA256="ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e"
PHPUNIT_KEY="D8406D0D82947747293778314AA394086372C20A"
PHP_IMAGE_DIGEST="sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143"
PHP_IMAGE="docker.io/library/php:5.6.40-cli@${PHP_IMAGE_DIGEST}"

if [[ "${USE_SUDO_DOCKER}" == "1" ]]; then
  DOCKER=(sudo docker)
else
  DOCKER=(docker)
fi

if ! command -v docker >/dev/null 2>&1; then
  echo "docker is required (set USE_SUDO_DOCKER=1 if docker needs sudo)." >&2
  exit 1
fi

download_and_verify_phpunit() {
  local work="$1"
  local phar="${work}/phpunit-${PHPUNIT_VERSION}.phar"
  local asc="${work}/phpunit-${PHPUNIT_VERSION}.phar.asc"
  curl -fsSL -o "${phar}" "https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar"
  curl -fsSL -o "${asc}" "https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar.asc"
  (
    cd "${work}"
    echo "${PHPUNIT_SHA256}  phpunit-${PHPUNIT_VERSION}.phar" | sha256sum -c - >/dev/null
  )
  local gpg_home status
  gpg_home="$(mktemp -d)"
  status="$(mktemp)"
  chmod 700 "${gpg_home}"
  GNUPGHOME="${gpg_home}" gpg --batch --keyserver hkps://keyserver.ubuntu.com --recv-keys "${PHPUNIT_KEY}"
  if ! GNUPGHOME="${gpg_home}" gpg --status-fd 3 --verify "${asc}" "${phar}" 3>"${status}" 2>/dev/null; then
    echo "PHPUnit PHAR GPG verification failed." >&2
    cat "${status}" >&2
    rm -rf "${gpg_home}" "${status}"
    exit 1
  fi
  if ! grep -qE "VALIDSIG (${PHPUNIT_KEY}|4AA394086372C20A)" "${status}"; then
    echo "Missing VALIDSIG for ${PHPUNIT_KEY}." >&2
    cat "${status}" >&2
    rm -rf "${gpg_home}" "${status}"
    exit 1
  fi
  rm -rf "${gpg_home}" "${status}"
  printf '%s' "${phar}"
}

validate_phpunit_output() {
  local output="$1"
  if grep -q 'Warning:' <<<"${output}"; then
    echo "Runner rejected output containing 'Warning:'." >&2
    exit 1
  fi
  local summary
  summary="$(grep -E '^OK \([0-9]+ tests, [0-9]+ assertions\)$' <<<"${output}" | tail -1 || true)"
  if [[ -z "${summary}" ]]; then
    echo "Runner did not find a PHPUnit OK summary line." >&2
    echo "${output}" >&2
    exit 1
  fi
  echo "${summary}"
}

run_suite_in_copy() {
  local repo_copy="$1"
  local phpunit_phar="$2"
  "${DOCKER[@]}" run --rm -i \
    -v "${repo_copy}:/workspace" \
    -v "${phpunit_phar}:/phpunit.phar:ro" \
    -w /workspace \
    "${PHP_IMAGE}" \
    bash -c 'set -e
rm -rf vendor .phpunit.cache
php -r "if (!extension_loaded(\"mbstring\") || !extension_loaded(\"zlib\")) { fwrite(STDERR, \"missing mbstring or zlib\\n\"); exit(1); }"
php /phpunit.phar --bootstrap tests/bootstrap.php tests'
}

expect_suite_failure() {
  local output="$1"
  if grep -q 'Warning:' <<<"${output}"; then
    return 0
  fi
  if ! grep -Eq '^OK \([0-9]+ tests, [0-9]+ assertions\)$' <<<"${output}"; then
    return 0
  fi
  return 1
}

prove_failure_modes() {
  local work phar copy output
  work="$(mktemp -d)"
  phar="$(download_and_verify_phpunit "${work}")"

  copy="$(mktemp -d)"
  cp -a "${ROOT}/." "${copy}/"
  sed -i "s/const VERSION = '1.33';/const VERSION = '1.32';/" "${copy}/tfpdf.php"
  output="$(run_suite_in_copy "${copy}" "${phar}" 2>&1 || true)"
  if ! expect_suite_failure "${output}"; then
    echo "Broken library copy should fail the runner." >&2
    echo "${output}" >&2
    exit 1
  fi

  copy="$(mktemp -d)"
  cp -a "${ROOT}/." "${copy}/"
  cat > "${copy}/tests/bootstrap.php" <<'EOF'
<?php
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if ($severity === E_USER_WARNING) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});
trigger_error('injected proof warning', E_USER_WARNING);
EOF
  output="$(run_suite_in_copy "${copy}" "${phar}" 2>&1 || true)"
  if ! expect_suite_failure "${output}"; then
    echo "Injected E_USER_WARNING should fail the runner." >&2
    echo "${output}" >&2
    exit 1
  fi
  echo "Failure-mode proofs passed."
}

work="$(mktemp -d)"
phar="$(download_and_verify_phpunit "${work}")"
set +e
output="$(run_suite_in_copy "${ROOT}" "${phar}")"
run_exit=$?
set -e
if [[ "${run_exit}" -ne 0 && -z "${output}" ]]; then
  echo "Docker/PHPUnit invocation failed with exit ${run_exit} and no output." >&2
  exit 1
fi
summary="$(validate_phpunit_output "${output}")"
printf '%s\n' "${output}"
echo "Validated: ${summary}"

if [[ "${RUN_FAILURE_PROOFS:-1}" == "1" ]]; then
  prove_failure_modes
fi
