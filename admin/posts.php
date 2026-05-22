<?php
/**
 * Al Bert Hoven — Dashboard de entradas
 */
require_once __DIR__ . '/config.php';

if (empty(ABH_PASS_HASH)) { header('Location: setup.php'); exit; }

session_name(ABH_SESSION_NAME);
session_start();

if (empty($_SESSION['abh_logged_in'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ABH — Bitácora</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=Newsreader:ital,wght@1,300&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg:      #080808;
  --bg2:     #0f0f0f;
  --bg3:     #141414;
  --border:  #1e1e1e;
  --border2: #2a2a2a;
  --chrome:  #c0c0c0;
  --chrome-hi: #f0f0f0;
  --chrome-lo: #404040;
  --accent:  #4a9eff;
  --accent-hi: #6bb3ff;
  --danger:  #e05555;
}

body {
  min-height: 100vh;
  background: var(--bg);
  font-family: 'DM Sans', sans-serif;
  color: var(--chrome);
  font-size: .9rem;
}

/* ── Header ── */
.topbar {
  position: sticky; top: 0; z-index: 100;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 2rem;
  height: 56px;
  background: var(--bg2);
  border-bottom: 1px solid var(--border);
}
.topbar-logo {
  font-family: 'Newsreader', serif;
  font-style: italic;
  font-size: 1.3rem;
  font-weight: 300;
  color: var(--chrome-hi);
  letter-spacing: .05em;
}
.topbar-logo span { font-family: 'DM Sans', sans-serif; font-style: normal; font-size: .65rem; color: var(--chrome-lo); text-transform: uppercase; letter-spacing: .12em; margin-left: .6rem; vertical-align: middle; }
.topbar-actions { display: flex; gap: 1rem; align-items: center; }
.btn-new {
  display: inline-flex; align-items: center; gap: .45rem;
  padding: .45rem 1rem;
  background: var(--accent);
  border: none;
  color: var(--bg);
  font-family: inherit; font-size: .75rem; font-weight: 500;
  letter-spacing: .1em; text-transform: uppercase;
  cursor: pointer; transition: background .2s;
}
.btn-new:hover { background: var(--accent-hi); }
.btn-link {
  background: none; border: none;
  color: var(--chrome-lo); font-family: inherit; font-size: .75rem;
  letter-spacing: .08em; text-transform: uppercase; cursor: pointer;
  text-decoration: none; transition: color .2s;
}
.btn-link:hover { color: var(--chrome); }

/* ── Main ── */
.main { max-width: 900px; margin: 0 auto; padding: 2.5rem 1.5rem; }

/* ── Notices ── */
.notice {
  padding: .7rem 1rem;
  font-size: .82rem;
  margin-bottom: 1.5rem;
  display: none;
}
.notice.ok    { background: #091409; border: 1px solid #1a4a1a; color: #6ec76e; }
.notice.error { background: #140909; border: 1px solid #4a1a1a; color: #e07070; }
.notice.show  { display: block; }

/* ── Posts list ── */
.section-title {
  font-size: .7rem; letter-spacing: .2em; text-transform: uppercase;
  color: var(--chrome-lo); margin-bottom: 1.2rem;
  display: flex; align-items: center; justify-content: space-between;
}
.posts-list { display: flex; flex-direction: column; gap: .6rem; }
.post-row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 1rem;
  align-items: center;
  background: var(--bg3);
  border: 1px solid var(--border);
  border-left: 2px solid var(--border2);
  padding: 1rem 1.2rem;
  transition: border-color .2s;
}
.post-row:hover { border-left-color: var(--accent); }
.post-row-meta { font-size: .72rem; color: var(--chrome-lo); margin-top: .25rem; }
.post-row-title { color: var(--chrome-hi); font-size: .92rem; font-weight: 400; }
.post-row-cat {
  display: inline-block;
  padding: .1rem .45rem;
  background: #151b25;
  border: 1px solid #1e2d40;
  color: var(--accent);
  font-size: .65rem; letter-spacing: .1em; text-transform: uppercase;
  margin-left: .5rem;
}
.post-actions { display: flex; gap: .5rem; }
.btn-edit, .btn-delete {
  padding: .35rem .7rem;
  font-family: inherit; font-size: .72rem;
  letter-spacing: .08em; text-transform: uppercase;
  cursor: pointer; border: 1px solid; transition: all .2s;
  background: none;
}
.btn-edit   { color: var(--chrome-lo); border-color: var(--border2); }
.btn-edit:hover { color: var(--accent); border-color: var(--accent); }
.btn-delete { color: var(--chrome-lo); border-color: var(--border2); }
.btn-delete:hover { color: var(--danger); border-color: var(--danger); }
.empty-state {
  text-align: center; padding: 3rem 1rem;
  color: var(--chrome-lo); font-size: .85rem;
  border: 1px dashed var(--border);
}

/* ── Drawer (panel lateral) ── */
.overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.65);
  z-index: 200;
  opacity: 0; pointer-events: none;
  transition: opacity .3s;
}
.overlay.open { opacity: 1; pointer-events: all; }

.drawer {
  position: fixed; top: 0; right: 0; bottom: 0;
  width: min(540px, 100vw);
  background: var(--bg2);
  border-left: 1px solid var(--border);
  z-index: 201;
  display: flex; flex-direction: column;
  transform: translateX(100%);
  transition: transform .3s ease;
}
.drawer.open { transform: translateX(0); }

.drawer-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.2rem 1.5rem;
  border-bottom: 1px solid var(--border);
  flex-shrink: 0;
}
.drawer-title {
  font-size: .7rem; letter-spacing: .2em; text-transform: uppercase;
  color: var(--chrome-lo);
}
.drawer-close {
  background: none; border: none;
  color: var(--chrome-lo); font-size: 1.3rem;
  cursor: pointer; line-height: 1;
  transition: color .2s;
}
.drawer-close:hover { color: var(--chrome-hi); }

.drawer-body {
  flex: 1; overflow-y: auto;
  padding: 1.5rem;
}

/* ── Form ── */
.field { margin-bottom: 1.3rem; }
label {
  display: block; font-size: .7rem; letter-spacing: .12em;
  text-transform: uppercase; color: #555; margin-bottom: .4rem;
}
input[type=text], input[type=number], select, textarea {
  width: 100%;
  padding: .6rem .85rem;
  background: var(--bg);
  border: 1px solid var(--border2);
  color: var(--chrome-hi);
  font-family: inherit; font-size: .88rem;
  outline: none; transition: border-color .2s;
  resize: vertical;
}
input[type=text]:focus,
input[type=number]:focus,
select:focus,
textarea:focus { border-color: var(--accent); }
select { appearance: none; cursor: pointer; }
textarea { min-height: 160px; line-height: 1.65; }
.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

.drawer-footer {
  padding: 1.2rem 1.5rem;
  border-top: 1px solid var(--border);
  display: flex; gap: .75rem; justify-content: flex-end;
  flex-shrink: 0;
}
.btn-cancel {
  padding: .55rem 1.2rem;
  background: none; border: 1px solid var(--border2);
  color: var(--chrome-lo); font-family: inherit; font-size: .78rem;
  letter-spacing: .1em; text-transform: uppercase; cursor: pointer;
  transition: all .2s;
}
.btn-cancel:hover { border-color: var(--chrome-lo); color: var(--chrome); }
.btn-save {
  padding: .55rem 1.4rem;
  background: var(--accent); border: none;
  color: var(--bg); font-family: inherit; font-size: .78rem;
  font-weight: 500; letter-spacing: .1em; text-transform: uppercase;
  cursor: pointer; transition: background .2s;
}
.btn-save:hover { background: var(--accent-hi); }
.btn-save:disabled { background: var(--border2); cursor: not-allowed; }

/* ── Spinner ── */
.spinner {
  display: inline-block; width: 14px; height: 14px;
  border: 2px solid transparent;
  border-top-color: currentColor;
  border-radius: 50%;
  animation: spin .6s linear infinite;
  vertical-align: middle;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>

<!-- Topbar -->
<header class="topbar">
  <div class="topbar-logo">
    Al Bert Hoven
    <span>Bitácora</span>
  </div>
  <div class="topbar-actions">
    <button class="btn-new" onclick="openDrawer()">
      <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor"><path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      Nueva entrada
    </button>
    <a href="logout.php" class="btn-link">Salir</a>
    <a href="../index.html" class="btn-link" target="_blank">Ver sitio →</a>
  </div>
</header>

<!-- Main -->
<main class="main">
  <div id="notice" class="notice"></div>

  <div class="section-title">
    <span>Entradas publicadas</span>
    <span id="post-count" style="color:#333">—</span>
  </div>

  <div id="posts-list" class="posts-list">
    <div class="empty-state">Cargando entradas…</div>
  </div>
</main>

<!-- Overlay -->
<div class="overlay" id="overlay" onclick="closeDrawer()"></div>

<!-- Drawer -->
<div class="drawer" id="drawer">
  <div class="drawer-header">
    <span class="drawer-title" id="drawer-title">Nueva entrada</span>
    <button class="drawer-close" onclick="closeDrawer()">×</button>
  </div>
  <div class="drawer-body">
    <form id="post-form" autocomplete="off">
      <input type="hidden" id="field-id" name="id" value="">

      <div class="field">
        <label for="field-title">Título *</label>
        <input type="text" id="field-title" name="title" required placeholder="Título de la entrada">
      </div>

      <div class="field">
        <label for="field-excerpt">Extracto</label>
        <textarea id="field-excerpt" name="excerpt" rows="3" placeholder="Resumen breve que aparece en la lista…"></textarea>
      </div>

      <div class="field">
        <label for="field-content">Contenido</label>
        <textarea id="field-content" name="content" rows="8" placeholder="Texto completo de la entrada…"></textarea>
      </div>

      <div class="field-row">
        <div class="field">
          <label for="field-category">Categoría</label>
          <select id="field-category" name="category">
            <option>Proceso Creativo</option>
            <option>Proyectos</option>
            <option>Reflexiones</option>
            <option>Noticias</option>
            <option>General</option>
          </select>
        </div>
        <div class="field">
          <label for="field-readtime">Tiempo de lectura (min)</label>
          <input type="number" id="field-readtime" name="readTime" min="1" max="60" value="3">
        </div>
      </div>
    </form>
  </div>
  <div class="drawer-footer">
    <button class="btn-cancel" onclick="closeDrawer()">Cancelar</button>
    <button class="btn-save" id="btn-save" onclick="savePost()">Guardar</button>
  </div>
</div>

<script>
const API = '../api/save.php';
const headers = {
  'Content-Type': 'application/json',
  'X-Requested-With': 'XMLHttpRequest'
};

let posts = [];
let editingId = null;

// ── Helpers ──────────────────────────────────────────────────
function showNotice(msg, type = 'ok') {
  const el = document.getElementById('notice');
  el.textContent = msg;
  el.className = 'notice show ' + type;
  setTimeout(() => el.classList.remove('show'), 4000);
}

function fmtDate(iso) {
  const [y, m, d] = iso.split('-');
  const months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
  return `${parseInt(d)} ${months[parseInt(m)-1]} ${y}`;
}

// ── Render ───────────────────────────────────────────────────
function render() {
  const container = document.getElementById('posts-list');
  document.getElementById('post-count').textContent = posts.length + (posts.length === 1 ? ' entrada' : ' entradas');

  if (!posts.length) {
    container.innerHTML = '<div class="empty-state">No hay entradas. Crea la primera.</div>';
    return;
  }

  container.innerHTML = posts.map(p => `
    <div class="post-row" data-id="${p.id}">
      <div>
        <div class="post-row-title">
          ${escHtml(p.title)}
          <span class="post-row-cat">${escHtml(p.category)}</span>
        </div>
        <div class="post-row-meta">${fmtDate(p.date)} · ${p.readTime} min lectura</div>
      </div>
      <div class="post-actions">
        <button class="btn-edit" onclick="openEdit('${p.id}')">Editar</button>
        <button class="btn-delete" onclick="deletePost('${p.id}')">Eliminar</button>
      </div>
    </div>
  `).join('');
}

function escHtml(str) {
  return String(str).replace(/[&<>"']/g, c =>
    ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// ── Load ─────────────────────────────────────────────────────
async function loadPosts() {
  try {
    const r = await fetch(API, { headers });
    if (!r.ok) throw new Error('HTTP ' + r.status);
    posts = await r.json();
    render();
  } catch (e) {
    document.getElementById('posts-list').innerHTML =
      '<div class="empty-state" style="color:#e07070">Error al cargar entradas: ' + e.message + '</div>';
  }
}

// ── Drawer ───────────────────────────────────────────────────
function openDrawer(post = null) {
  editingId = post ? post.id : null;
  document.getElementById('drawer-title').textContent = post ? 'Editar entrada' : 'Nueva entrada';
  document.getElementById('field-id').value       = post ? post.id      : '';
  document.getElementById('field-title').value    = post ? post.title   : '';
  document.getElementById('field-excerpt').value  = post ? post.excerpt : '';
  document.getElementById('field-content').value  = post ? post.content : '';
  document.getElementById('field-readtime').value = post ? post.readTime : '3';

  const cat = document.getElementById('field-category');
  if (post) {
    [...cat.options].forEach(o => o.selected = (o.value === post.category));
  } else {
    cat.selectedIndex = 0;
  }

  document.getElementById('overlay').classList.add('open');
  document.getElementById('drawer').classList.add('open');
  setTimeout(() => document.getElementById('field-title').focus(), 300);
}

function closeDrawer() {
  document.getElementById('overlay').classList.remove('open');
  document.getElementById('drawer').classList.remove('open');
  document.getElementById('post-form').reset();
  editingId = null;
}

function openEdit(id) {
  const post = posts.find(p => p.id === id);
  if (post) openDrawer(post);
}

// ── Save ─────────────────────────────────────────────────────
async function savePost() {
  const btn = document.getElementById('btn-save');
  const title = document.getElementById('field-title').value.trim();
  if (!title) { showNotice('El título es obligatorio.', 'error'); return; }

  const payload = {
    id:       document.getElementById('field-id').value,
    title,
    excerpt:  document.getElementById('field-excerpt').value.trim(),
    content:  document.getElementById('field-content').value.trim(),
    category: document.getElementById('field-category').value,
    readTime: parseInt(document.getElementById('field-readtime').value) || 3
  };

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span>';

  try {
    const method = editingId ? 'PUT' : 'POST';
    const r = await fetch(API, { method, headers, body: JSON.stringify(payload) });
    const data = await r.json();

    if (!r.ok || data.error) throw new Error(data.error || 'Error del servidor');

    closeDrawer();
    await loadPosts();
    showNotice(editingId ? 'Entrada actualizada.' : 'Entrada publicada.');
  } catch (e) {
    showNotice('Error: ' + e.message, 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Guardar';
  }
}

// ── Delete ───────────────────────────────────────────────────
async function deletePost(id) {
  const post = posts.find(p => p.id === id);
  if (!post) return;
  if (!confirm(`¿Eliminar "${post.title}"?\nEsta acción no se puede deshacer.`)) return;

  try {
    const r = await fetch(API, {
      method: 'DELETE',
      headers,
      body: JSON.stringify({ id })
    });
    const data = await r.json();
    if (!r.ok || data.error) throw new Error(data.error || 'Error del servidor');
    await loadPosts();
    showNotice('Entrada eliminada.');
  } catch (e) {
    showNotice('Error: ' + e.message, 'error');
  }
}

// ── Escape key ───────────────────────────────────────────────
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeDrawer();
});

// ── Init ─────────────────────────────────────────────────────
loadPosts();
</script>
</body>
</html>
