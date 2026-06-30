# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

An internal, password-protected tool for Autura (codename "Heckle"): a vehicle **valuation tool** plus several **market/seller/buyer reports** built from ~18 months of Autura Marketplace impound-auction sales. Plain **PHP 8.2, no framework, no database** — JSON files in `data/` are the entire data store. Vanilla JS on the front end (no build step, no npm).

## Commands

- **Syntax check** (do this after every PHP edit): `php -l <file>.php`
- **Rebuild the dataset** from CSVs: `php build-amr-data.php --date="Jun 1, 2026"` — reads `data/heckel/*.csv`, writes `data/amr-data.json`, `data/amr-meta.json`, `data/amr-buyers.json`. The web equivalent is the upload UI at `/update` (`update.php`), which drops the CSV in `data/heckel/` and runs the importer.
- **Deploy** (push to GitHub *and* the live server): `git push origin main && git push hostinger main`. The `hostinger` remote is a git repo on the production host whose post-receive hook checks the working tree out to the live site. **Data files are gitignored and never deploy via git** — they already live on the server (or are scp'd out-of-band).
- **Server shell** (read-only checks, log out-of-band data): `ssh -p 65002 u535581001@us-phx-web1238.main-hosting.eu` then `cd domains/auturareport.com/public_html`.
- **Local dev**: MAMP serving this dir at `https://auturareport:8890` (`config.php` detects local vs prod by host).

There is **no automated test suite.** Verify changes by rendering a page through the PHP CLI with a faked session and driving it with headless Chrome — see "Verifying changes" below.

## Architecture

**Pages = top-level `.php` files, routed by `.htaccess`.** Each page sets `$page_title` / `$body_class` / `$canonical` / `$extra_head`, then `include includes/header.php` … content … `include includes/footer.php`. Clean URLs (e.g. `/autura-market-report` → `market.php`) are mapped by RewriteRules. When adding a page, add its route to `.htaccess`, a nav entry in `includes/header.php` (`$top_links` / `$menus`), and a `/changelog` entry.

**Two-tier auth.**
- `includes/auth.php` (required first on every page) enforces login. Per-user passwords are bootstrapped off `AMR_DEFAULT_PASSWORD` (`'heckle'`) — first login forces the user to set their own (hashed, in `data/users.json`). Handles 30-min idle timeout and `/logout`.
- A second **access code (PIN)** gate, `amr_customer_gate()` in `includes/customer_data.php`, protects the customer/buyer reports (`customer.php`, `customer-research.php`, `impound-map.php`, `buyer-report.php`). It sets `$_SESSION['cr_auth']`. The gated JSON feed `buyer-data.php` checks both `amr_auth` and `cr_auth`.

**Data model — compact, dictionary-interned JSON.** `amr-data.json` records are int arrays, not objects, to keep the file small:
`[make_idx, model_idx, year, price, flags, region_idx, doc_idx, odo, month_idx, seller_idx]` where `flags` bit0=has_key, bit1=no_key, bit2=starts; `month_idx`/`seller_idx` are `-1` if unknown; the `makes`/`models`/`sellers`/… arrays are the dictionaries. `amr-buyers.json` is the parallel **buyer** dataset (`[month, region, seller, auction, buyer, city, state, type, make, price, reserve]`) — it contains PII and is gated separately. The importer (`build-amr-data.php`) detects the CSV header **by column names** so both the old and new export layouts work, normalizes makes/models/docs/buyer-types/states via lookup tables, and derives the sale month from the sold-date column.

**Reports filter client-side.** Bigger reports fetch their dataset from a gated PHP feed (`data-json.php` for the market report, `buyer-data.php` for the buyer report) and do all aggregation/filtering in vanilla JS by rebuilding `innerHTML` from template literals. Smaller reports inline an aggregated payload from PHP. The market report and buyer report share the `makeMultiSelect` Region/Seller/Group filter widget (currently duplicated in both files).

**Shared pieces:**
- `includes/us_map.php` — `amr_state_paths()` projects `assets/us-states.geojson` into SVG path strings (lower-48 + DC + AK/HI insets) for choropleths.
- Seller groups live in `data/seller-groups.json` (managed at `/seller-groups`) and drive the **Group** filter on the market and buyer reports.
- The period-comparison color convention is global in `assets/css/main.css`: `--p-cur` (this 60d, near-black), `--p-prev` (prior 60d, blue), `--p-year` (last-year, orange).

## PII & data-file rules (important)

`data/` and `assets/csvs/` hold private sales + CRM data. Anything sensitive must be blocked from the web **and** from git. When you create a new data file:
1. Add a `RewriteRule ^data/<name>\.json$ - [F,L]` block in `.htaccess` (and serve it, if needed, only through a session-checked PHP proxy).
2. Add it to `.gitignore`.
   `data/heckel/` (raw CSVs) and `assets/csvs/` (CRM exports) are already blocked + ignored. Never `git add` files under `data/`.

## Conventions & gotchas

- **JSON writes can silently truncate.** Malformed UTF-8 in seller/buyer names once made `json_encode` return `false` and wrote an empty file → production outage. Always encode with `JSON_INVALID_UTF8_SUBSTITUTE` and refuse to write empty/`false` output.
- **Server-side mutations must be wipe-proof.** Endpoints that persist user edits (seller groups, impound marks) reject empty/malformed payloads, require explicit confirmation before clearing everything, back up to `data/_backups/` before writing, and use `flock`. Follow this pattern for any new persisted state.
- **`$extra_head` is a single-quoted PHP string.** CSS `content:` values inside it must use **double** quotes (`content: "+";`), not single quotes — single quotes terminate the PHP string.
- **CSS vars in SVG** must be set via `style="fill:var(--x)"`, never the `fill="var(--x)"` attribute (the attribute form doesn't resolve and renders black).
- **Fixed header overlap:** the site header is `position: fixed`, height `var(--nav-h)` (68px). A page's first section needs top padding `calc(var(--nav-h) + N)` or its `<h1>` tucks under the menu.
- `fgetcsv` on PHP 8.x: pass the explicit `$escape` argument (`''`) to avoid the deprecation.

## Verifying changes

`php -l` catches syntax. To verify behavior, render the page with a faked session and (for data-driven pages) a stubbed `fetch`, then drive it with headless Chrome:

```bash
php -r '
ini_set("session.save_path",sys_get_temp_dir()); session_start();
$_SESSION["amr_auth"]=true; $_SESSION["amr_email"]="u@autura.com"; $_SESSION["amr_last"]=time(); $_SESSION["cr_auth"]=true;
$_SERVER["REQUEST_METHOD"]="GET"; $_SERVER["REQUEST_URI"]="/autura-market-report";
ob_start(); @include "market.php"; $o=ob_get_clean();
$stub="<script>window.fetch=()=>Promise.resolve({ok:true,json:()=>Promise.resolve(".file_get_contents("data/amr-data.json").")});</script>";
echo str_replace("</head>",$stub."</head>",$o);
' > /tmp/page.html
"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless=new --disable-gpu --dump-dom \
  --virtual-time-budget=8000 "file:///tmp/page.html"
```

Inject a `<pre id="DRIVE">` + `<script setTimeout(...)>` before `</body>` that clicks/reads the DOM and writes a JSON marker, then `grep -oE 'DRIVE \{.*\}'` the dumped DOM. Note: on `file://`, `/assets/css/main.css` won't load — inline `main.css` into a `<style>` when you need real colors/layout for a screenshot.
