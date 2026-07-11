#!/usr/bin/env bash
# Golden parity harness — Fase 2.5 wiring layer modern (#6105).
#
# Merekam respons endpoint representatif (status + header kunci + body utuh)
# sebagai fixture, lalu membandingkannya byte-per-byte sebelum vs sesudah
# wiring. Filosofi sama dengan parity T2–T4 #6085: perilaku respons TIDAK
# boleh berubah teramati.
#
# Prasyarat:
#   - Server jalan: (cd public && php -S localhost:8899) — host localhost => STAGE=local
#   - apps/home/xml/dsnSource.local.xml     → MySQL lokal (tabel daftar_aset terisi)
#   - apps/gov2gajah/xml/dsnSource.local.xml → driver supabase gajah (anon key)
#     (keduanya gitignored; tanpa file gajah, dua kasus gajah_* boleh di-skip
#      via GOLDEN_SKIP="gajah_page gajah_table_json")
#
# Pemakaian:
#   tests/golden/golden.sh record   # rekam fixture (SEBELUM menyentuh wiring)
#   tests/golden/golden.sh verify   # bandingkan respons live vs fixture
set -u

BASE="${GOLDEN_BASE:-http://localhost:8899}"
DIR="$(cd "$(dirname "$0")" && pwd)"
FIX="$DIR/fixtures"
OUT="${GOLDEN_OUT:-$(mktemp -d)}"
MODE="${1:-verify}"
SKIP=" ${GOLDEN_SKIP:-} "

pass=0; fail=0; skipped=0; failed_names=()

# fetch <name> <method> <path> <accept> [json-body]
# Simpan: STATUS, CONTENT-TYPE, LOCATION, baris kosong, lalu body utuh.
fetch() {
    local name="$1" method="$2" path="$3" accept="$4" body="${5:-}"
    local dest="$1.golden" hdr args
    hdr="$(mktemp)"
    args=(-s -D "$hdr" -o "$OUT/$name.body" -X "$method")
    [ -n "$accept" ] && args+=(-H "Accept: $accept")
    [ -n "$body" ] && args+=(-H 'Content-Type: application/json' -d "$body")
    curl "${args[@]}" "$BASE$path"

    local status ct loc
    status=$(awk 'toupper($0) ~ /^HTTP/ {print $2; exit}' "$hdr")
    ct=$(awk -F': ' 'tolower($1)=="content-type" {print $2; exit}' "$hdr" | tr -d '\r')
    loc=$(awk -F': ' 'tolower($1)=="location" {print $2; exit}' "$hdr" | tr -d '\r')
    {
        echo "REQUEST $method $path accept=[$accept] body=[$body]"
        echo "STATUS $status"
        echo "CONTENT-TYPE $ct"
        echo "LOCATION $loc"
        echo
        cat "$OUT/$name.body"
    } > "$OUT/$dest"
    rm -f "$hdr" "$OUT/$name.body"
}

# case <name> <method> <path> <accept> [json-body]
run_case() {
    local name="$1"
    if [[ "$SKIP" == *" $name "* ]]; then
        echo "SKIP  $name"; skipped=$((skipped+1)); return
    fi
    fetch "$@"
    if [ "$MODE" = "record" ]; then
        mkdir -p "$FIX"
        cp "$OUT/$name.golden" "$FIX/$name.golden"
        echo "REC   $name"
    else
        if cmp -s "$FIX/$name.golden" "$OUT/$name.golden"; then
            echo "PASS  $name"; pass=$((pass+1))
        else
            echo "FAIL  $name  (diff: diff $FIX/$name.golden $OUT/$name.golden)"
            fail=$((fail+1)); failed_names+=("$name")
        fi
    fi
}

# ---- halaman (tier FILE / template) ----
run_case page_root          GET  "/"                        ""
run_case page_index_php     GET  "/index.php"               ""
run_case page_maintenance   GET  "/maintenance.html"        ""
run_case viewer_csv         GET  "/home/viewer/csv/sample"  ""
run_case viewer_md_notfound GET  "/home/viewer/md/README"   ""
run_case gov2instansi_page  GET  "/gov2instansi"            ""

# ---- crud (tier SQL — butuh MySQL lokal) ----
run_case crud_page          GET  "/home/crud"               ""
run_case crud_page_qs       GET  "/home/crud?cmd=xyz"       ""
run_case crud_json_table    POST "/home/crud" "application/json" '{"cmd":"table","scroll":{"scroll":1}}'
run_case crud_json_count    POST "/home/crud" "application/json" '{"cmd":"count","id":0}'
run_case crud_json_edit     POST "/home/crud" "application/json" '{"cmd":"edit","id":1}'
run_case crud_json_fields   POST "/home/crud" "application/json" '{"cmd":"fields"}'

# ---- gov2login (alur auth) ----
run_case login_page         GET  "/gov2login"               ""
run_case login_login_page   GET  "/gov2login/login"         ""
run_case login_auth_json    POST "/gov2login" "application/json" '{"cmd":"auth","username":"nouser","password":"x"}'

# ---- gov2gajah (tier REST — driver supabase, env/DSN-gated) ----
run_case gajah_page         GET  "/gov2gajah"               ""
run_case gajah_table_json   GET  "/gov2gajah/table"         "application/json"

# ---- jalur error & legacy route ----
run_case err_no_app         GET  "/nosuchapp"               ""
run_case err_no_route       GET  "/home/nosuchroute"        ""
run_case err_legacy_login   GET  "/login"                   ""
run_case err_slogin         GET  "/slogin?client=abc"       ""

echo
if [ "$MODE" = "record" ]; then
    echo "Fixture terekam di $FIX ($(ls "$FIX" | wc -l) file)."
else
    echo "HASIL: $pass pass, $fail fail, $skipped skip."
    if [ "$fail" -gt 0 ]; then
        printf 'FAILED: %s\n' "${failed_names[@]}"
        exit 1
    fi
fi
