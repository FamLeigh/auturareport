<?php
require_once __DIR__ . '/includes/auth.php';        // require Autura login
require_once __DIR__ . '/includes/functions.php';

$groups_file = __DIR__ . '/data/seller-groups.json';

// ── Save the full groups map (AJAX) ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_groups'])) {
    $in = json_decode((string) $_POST['save_groups'], true);
    $clean = [];
    if (is_array($in)) {
        foreach ($in as $name => $members) {
            $name = trim((string) $name);
            if ($name === '' || !is_array($members)) continue;
            $m = array_values(array_unique(array_filter(array_map(fn($s) => trim((string) $s), $members), fn($s) => $s !== '')));
            $clean[$name] = $m;
        }
    }
    file_put_contents($groups_file, json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

$groups = file_exists($groups_file) ? (json_decode(file_get_contents($groups_file), true) ?: []) : [];

// Seller list (with volume) from the AMR dataset.
$amr = file_exists(__DIR__ . '/data/amr-data.json') ? json_decode(file_get_contents(__DIR__ . '/data/amr-data.json'), true) : null;
$sellers = [];
if ($amr && !empty($amr['records'])) {
    $cnt = [];
    foreach ($amr['records'] as $r) { $si = $r[9] ?? -1; if ($si >= 0) { $nm = $amr['sellers'][$si]; $cnt[$nm] = ($cnt[$nm] ?? 0) + 1; } }
    arsort($cnt);
    foreach ($cnt as $nm => $c) $sellers[] = ['name' => $nm, 'count' => $c];
}

$groups_json  = json_encode($groups,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}';
$sellers_json = json_encode($sellers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: '[]';

$page_title = 'Define Seller Groups';
$body_class = 'page-sellergroups';
$canonical  = '/seller-groups';
$extra_head = '<meta name="robots" content="noindex, nofollow">
<style>
.sg-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin:18px 0 16px; }
.sg-bar input { background:var(--surface); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:14px; padding:9px 12px; min-width:240px; }
.sg-bar input:focus { outline:none; border-color:var(--accent); }
.sg-btn { background:var(--accent); border:none; border-radius:8px; color:#000; font-size:13px; font-weight:700; padding:9px 16px; cursor:pointer; }
.sg-btn:disabled { opacity:.45; cursor:default; }
.sg-btn.ghost { background:none; border:1px solid var(--border); color:var(--text-muted); }
.sg-btn.ghost:hover { border-color:var(--accent); color:var(--accent); }
.sg-status { font-size:12px; color:var(--text-muted); }
.sg-grid { display:grid; grid-template-columns:300px 1fr; gap:18px; align-items:start; }
@media (max-width:760px){ .sg-grid{ grid-template-columns:1fr; } }
.sg-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:14px; }
.sg-glist { display:flex; flex-direction:column; gap:6px; }
.sg-gitem { display:flex; align-items:center; gap:8px; padding:9px 11px; border:1px solid var(--border); border-radius:8px; cursor:pointer; }
.sg-gitem:hover { background:var(--surface-2); }
.sg-gitem.on { border-color:var(--accent); background:var(--accent-glow); }
.sg-gitem .nm { flex:1; font-weight:600; font-size:14px; }
.sg-gitem .ct { font-size:11px; color:var(--text-muted); }
.sg-gitem .del { background:none; border:none; color:var(--text-muted); cursor:pointer; font-size:15px; line-height:1; padding:2px 4px; }
.sg-gitem .del:hover { color:#c0392b; }
.sg-empty { color:var(--text-muted); font-size:13px; padding:18px; text-align:center; }
.sg-ed-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; flex-wrap:wrap; }
.sg-ed-head h3 { font-size:1.05rem; }
.sg-search { background:var(--surface-2); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:13px; padding:8px 12px; width:100%; margin-bottom:10px; }
.sg-search:focus { outline:none; border-color:var(--accent); }
.sg-sellers { max-height:520px; overflow-y:auto; border:1px solid var(--border); border-radius:8px; }
.sg-sopt { display:flex; align-items:center; gap:10px; padding:8px 12px; font-size:13px; cursor:pointer; border-bottom:1px solid rgba(0,0,0,.05); }
[data-theme="dark"] .sg-sopt { border-bottom-color:rgba(255,255,255,.05); }
.sg-sopt:hover { background:var(--surface-2); }
.sg-sopt input { accent-color:var(--accent); cursor:pointer; }
.sg-sopt .sc { margin-left:auto; font-size:11px; color:var(--text-muted); font-variant-numeric:tabular-nums; }
.sg-tools { display:flex; gap:8px; margin-bottom:8px; }
.sg-tools button { background:none; border:none; color:var(--accent); font-size:12px; cursor:pointer; padding:2px 4px; }
</style>';

include __DIR__ . '/includes/header.php';
?>
<div class="container">
  <section class="cr-hero" style="padding:56px 0 8px;">
    <h1 style="font-size:clamp(1.7rem,4vw,2.4rem);margin-bottom:6px;">Define Seller Groups</h1>
    <p class="cr-sub" style="font-size:13px;color:var(--text-muted);">Create named groups of sellers, then use the <strong>Group</strong> filter on the Market Report. Changes save to the server.</p>
  </section>

  <div class="sg-bar">
    <input id="sg-newname" type="text" placeholder="New group name" maxlength="60">
    <button class="sg-btn" id="sg-create">Create group</button>
    <span style="margin-left:auto;display:flex;align-items:center;gap:12px;">
      <span class="sg-status" id="sg-status"></span>
      <button class="sg-btn" id="sg-save" disabled>Save changes</button>
    </span>
  </div>

  <div class="sg-grid">
    <div class="sg-card"><div class="sg-glist" id="sg-groups"></div></div>
    <div class="sg-card" id="sg-editor"></div>
  </div>
</div>

<script>
let GROUPS  = <?= $groups_json ?>;     // { "Group": ["Seller", ...] }
const SELLERS = <?= $sellers_json ?>;  // [{name, count}]
let selected = Object.keys(GROUPS)[0] || null;
let dirty = false;
let term = '';
const esc = s => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const fmtN = n => Number(n||0).toLocaleString();

function setDirty(v){ dirty = v; const b=document.getElementById('sg-save'); b.disabled=!v; document.getElementById('sg-status').textContent = v ? 'Unsaved changes' : ''; }

function renderGroups(){
  const names = Object.keys(GROUPS).sort((a,b)=>a.localeCompare(b));
  const el = document.getElementById('sg-groups');
  el.innerHTML = names.length ? names.map(n => `
    <div class="sg-gitem ${n===selected?'on':''}" data-group="${esc(n)}">
      <span class="nm">${esc(n)}</span><span class="ct">${fmtN(GROUPS[n].length)}</span>
      <button class="del" data-del="${esc(n)}" title="Delete group">&times;</button>
    </div>`).join('') : `<div class="sg-empty">No groups yet — create one above.</div>`;
}

function renderEditor(){
  const ed = document.getElementById('sg-editor');
  if (!selected || !GROUPS[selected]) { ed.innerHTML = `<div class="sg-empty">Select or create a group to choose its sellers.</div>`; return; }
  const members = new Set(GROUPS[selected]);
  const ft = term.trim().toLowerCase();
  const rows = SELLERS.filter(s => !ft || s.name.toLowerCase().includes(ft)).map(s =>
    `<label class="sg-sopt"><input type="checkbox" data-seller="${esc(s.name)}" ${members.has(s.name)?'checked':''}><span>${esc(s.name)}</span><span class="sc">${fmtN(s.count)}</span></label>`
  ).join('');
  ed.innerHTML = `
    <div class="sg-ed-head"><h3>${esc(selected)}</h3><span class="cr-sub" style="font-size:12px;color:var(--text-muted)">${fmtN(members.size)} of ${fmtN(SELLERS.length)} sellers</span></div>
    <div class="sg-tools"><button id="sg-all">Select all shown</button><button id="sg-none">Clear all</button></div>
    <input class="sg-search" id="sg-search" type="text" placeholder="Search sellers…" value="${esc(term)}">
    <div class="sg-sellers">${rows || '<div class="sg-empty">No sellers match.</div>'}</div>`;
  const s = document.getElementById('sg-search'); s.addEventListener('input', e => { term = e.target.value; renderEditor(); });
  document.getElementById('sg-all').addEventListener('click', () => { const ft=term.trim().toLowerCase(); SELLERS.filter(x=>!ft||x.name.toLowerCase().includes(ft)).forEach(x=>members.add(x.name)); GROUPS[selected]=[...members]; setDirty(true); renderGroups(); renderEditor(); });
  document.getElementById('sg-none').addEventListener('click', () => { GROUPS[selected]=[]; setDirty(true); renderGroups(); renderEditor(); });
}

function render(){ renderGroups(); renderEditor(); }

document.getElementById('sg-create').addEventListener('click', () => {
  const inp = document.getElementById('sg-newname'); const name = inp.value.trim();
  if (!name) return;
  if (GROUPS[name]) { alert('A group with that name already exists.'); return; }
  GROUPS[name] = []; selected = name; inp.value = ''; setDirty(true); render();
});
document.getElementById('sg-newname').addEventListener('keydown', e => { if (e.key==='Enter') document.getElementById('sg-create').click(); });

document.addEventListener('click', e => {
  const del = e.target.closest('[data-del]');
  if (del) { e.stopPropagation(); const n=del.dataset.del; if (confirm('Delete group "'+n+'"?')) { delete GROUPS[n]; if (selected===n) selected=Object.keys(GROUPS)[0]||null; setDirty(true); render(); } return; }
  const g = e.target.closest('[data-group]'); if (g) { selected = g.dataset.group; term=''; render(); return; }
});
document.addEventListener('change', e => {
  const cb = e.target.closest('input[data-seller]'); if (!cb || !selected) return;
  const set = new Set(GROUPS[selected]);
  if (cb.checked) set.add(cb.dataset.seller); else set.delete(cb.dataset.seller);
  GROUPS[selected] = [...set]; setDirty(true); renderGroups();
});
document.getElementById('sg-save').addEventListener('click', () => {
  document.getElementById('sg-status').textContent = 'Saving…';
  fetch(location.pathname, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'save_groups='+encodeURIComponent(JSON.stringify(GROUPS)) })
    .then(r=>r.json()).then(d=>{ if(d&&d.ok){ setDirty(false); document.getElementById('sg-status').textContent='Saved ✓'; setTimeout(()=>{ if(!dirty) document.getElementById('sg-status').textContent=''; }, 2000); } else throw 0; })
    .catch(()=>{ document.getElementById('sg-status').textContent='Save failed'; });
});
window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue=''; } });

render();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
