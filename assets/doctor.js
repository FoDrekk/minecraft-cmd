/* ================================================
   doctor.js — Command Doctor & Explainer UI
   ================================================ */
(function () {
  'use strict';

  var input, out, btn, timer = null;

  function icon(level) { return level === 'error' ? '⛔' : (level === 'warn' ? '⚠️' : 'ℹ️'); }
  var esc = function (s) { return MC.escapeHtml(s); };

  function renderDoctor(r) {
    if (!r.problems.length) {
      out.innerHTML = '<div class="all-clear"><div class="all-clear-title">✅ Nothing wrong found</div>' +
        '<div class="muted" style="font-size:13px">This looks fine for ' + esc(r.version.label) +
        '. The Doctor cannot check every id in the game, so an in-game error may still mean a typo it does not know about.</div></div>';
      return;
    }

    var html = r.problems.map(function (p) {
      return '<div class="finding is-' + esc(p.level) + '">' +
        '<div class="finding-head"><span class="finding-icon">' + icon(p.level) + '</span>' +
        '<span class="finding-title">' + esc(p.title) + '</span>' +
        (p.version ? '<span class="ver-tag">Version</span>' : '') + '</div>' +
        '<div class="finding-body">' +
        '<div class="finding-part"><span class="finding-label">Why</span>' + esc(p.why) + '</div>' +
        (p.fix ? '<div class="finding-part"><span class="finding-label">Fix</span>' + esc(p.fix) + '</div>' : '') +
        '</div></div>';
    }).join('');

    if (r.fixed) {
      html += '<div class="card" style="margin-top:16px"><div class="card-header">' +
        '<span class="card-header-dot" style="background:var(--green)"></span>Fixed for ' + esc(r.version.label) + '</div>' +
        '<div class="card-body"><div class="cmdout" id="doc-fixed" data-cmdout data-tab="doctor">' +
        '<div class="cmdout-head"><span class="cmdout-label">Corrected command</span><span class="cmdout-meta" data-meta></span></div>' +
        '<div class="cmdout-body" data-cmd></div><div class="cmdout-warn" data-warn hidden></div>' +
        '<div class="cmdout-actions">' +
        '<button class="btn btn-green" data-act="copy">📋 Copy fixed command</button>' +
        '<button class="btn btn-purple" data-act="fav">⭐ Library</button>' +
        '</div></div></div></div>';
    }

    out.innerHTML = html;
    if (r.fixed) MC.setCommand('doc-fixed', r.fixed, { tab: 'doctor' });
  }

  function renderExplain(r) {
    if (!r.summary) { out.innerHTML = ''; return; }
    out.innerHTML = '<div class="card"><div class="card-header">' +
      '<span class="card-header-dot" style="background:var(--blue)"></span>What this does</div>' +
      '<div class="card-body"><div class="exp-summary">' + esc(r.summary) + '</div>' +
      '<div class="step-list">' + r.steps.map(function (s) {
        return '<div class="exp-step"><div class="exp-step-label">' + esc(s[0]) + '</div>' +
          '<div class="exp-step-text">' + esc(s[1]) + '</div></div>';
      }).join('') + '</div></div></div>';
  }

  function run() {
    var cmd = input.value.trim();
    if (!cmd) { out.innerHTML = ''; return; }
    var action = MODE === 'explain' ? 'explain' : 'doctor';
    btn.disabled = true;
    MC.api(action, { command: cmd, version: MC.ver() }).then(function (d) {
      btn.disabled = false;
      if (!d.ok) {
        out.innerHTML = '<div class="finding is-error"><div class="finding-head">' +
          '<span class="finding-icon">⛔</span><span class="finding-title">Could not check that</span></div>' +
          '<div class="finding-body"><div class="finding-part">' + esc(d.error || 'Something went wrong. Try again.') +
          '</div></div></div>';
        return;
      }
      if (MODE === 'explain') renderExplain(d.result); else renderDoctor(d.result);
      MC.remember({ type: 'tool', icon: '🩺', title: (MODE === 'explain' ? 'Explained: ' : 'Checked: ') + cmd.slice(0, 48),
                    href: 'doctor.php' + (MODE === 'explain' ? '?mode=explain' : '') });
    });
  }
  window.run = run;

  document.addEventListener('DOMContentLoaded', function () {
    input = document.getElementById('doc-input');
    out   = document.getElementById('doc-output');
    btn   = document.getElementById('doc-run');
    if (!input) return;

    btn.addEventListener('click', run);
    input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(run, 450); });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); run(); }
    });

    document.querySelectorAll('[data-example]').forEach(function (b) {
      b.addEventListener('click', function () { input.value = b.dataset.example; run(); });
    });

    // Re-check when the version changes — most findings depend on it.
    document.addEventListener('mc:version', run);

    var pre = new URLSearchParams(location.search).get('c');
    if (pre) { input.value = pre; run(); }
  });
})();
