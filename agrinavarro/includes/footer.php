<?php require_once __DIR__ . '/version.php'; ?>
</div>
<footer class="app-footer"><span><strong>Made by:</strong> Navarro, Rey Anderson D. <span class="text-muted ms-2">BSIS 3-1</span></span><nav class="d-flex align-items-center gap-3" aria-label="Footer links"><a href="https://github.com/ReyNavarro" target="_blank" rel="noopener noreferrer">Portfolio</a><a href="https://github.com/ReyNavarro" target="_blank" rel="noopener noreferrer">GitHub</a><a href="<?= getBaseUrl() ?>/logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a><span class="app-version" aria-label="Application version">v<?= htmlspecialchars(APP_VERSION, ENT_QUOTES, 'UTF-8') ?></span></nav></footer>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Premium sidebar: mobile overlay + desktop hover expand
(function () {
  var sidebar = document.getElementById('sidebar');
  var toggle = document.getElementById('mobileNavToggle');
  var backdrop = document.getElementById('sidebarBackdrop');
  var mobileScreen = window.matchMedia('(max-width: 768px)');

  if (!sidebar || !toggle || !backdrop) return;

  function setMenuOpen(isOpen) {
    sidebar.classList.toggle('is-open', isOpen);
    document.body.classList.toggle('sidebar-open', isOpen);
    toggle.setAttribute('aria-expanded', String(isOpen));
    toggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
    toggle.innerHTML = isOpen
      ? '<i class="bi bi-x-lg" aria-hidden="true"></i>'
      : '<i class="bi bi-list" aria-hidden="true"></i>';
    backdrop.hidden = !isOpen;

    if (mobileScreen.matches) {
      sidebar.inert = !isOpen;
    }

    if (isOpen) {
      var firstLink = sidebar.querySelector('a[href]');
      if (firstLink) firstLink.focus();
    } else if (document.activeElement !== toggle) {
      toggle.focus();
    }
  }

  toggle.addEventListener('click', function () {
    setMenuOpen(toggle.getAttribute('aria-expanded') !== 'true');
  });
  backdrop.addEventListener('click', function () {
    setMenuOpen(false);
  });
  sidebar.addEventListener('click', function (event) {
    if (mobileScreen.matches && event.target.closest('a')) setMenuOpen(false);
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
      setMenuOpen(false);
    }
  });
  mobileScreen.addEventListener('change', function (event) {
    document.body.classList.remove('sidebar-open');
    sidebar.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open navigation menu');
    toggle.innerHTML = '<i class="bi bi-list" aria-hidden="true"></i>';
    backdrop.hidden = true;
    sidebar.inert = event.matches;
  });

  sidebar.inert = mobileScreen.matches;
})();

setTimeout(function(){document.querySelectorAll('.alert').forEach(function(el){bootstrap.Alert.getOrCreateInstance(el).close();});},5000);// Update searchable tables as the user types, using each module's existing safe server query.
document.querySelectorAll('[data-live-search]').forEach(function(input){
  var target=document.getElementById(input.dataset.liveTarget),form=input.form,endpoint=input.dataset.liveSearch,timer,controller;
  if(!target)return;
  function refresh(){if(controller)controller.abort();controller=new AbortController();var params=form?new URLSearchParams(new FormData(form)):new URLSearchParams({search:input.value});params.delete('page');fetch(endpoint+'?'+params.toString(),{signal:controller.signal,headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(response){if(!response.ok)throw new Error('Search request failed');return response.text();}).then(function(html){target.innerHTML=html;var pagination=document.querySelector('[data-live-pagination]');if(pagination)pagination.hidden=true;}).catch(function(error){if(error.name!=='AbortError'){target.innerHTML='<tr><td colspan="8" class="text-center text-danger py-3">Search failed. Please try again.</td></tr>';}});}
  function schedule(){clearTimeout(timer);timer=setTimeout(refresh,250);}
  input.addEventListener('input',schedule);
  if(form){form.addEventListener('change',function(e){if(e.target.matches('select'))schedule();});form.addEventListener('submit',function(e){e.preventDefault();refresh();});}
});
(function(){var card=document.querySelector('.tilt-card');if(!card||matchMedia('(prefers-reduced-motion: reduce)').matches)return;card.addEventListener('pointermove',function(e){var r=card.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;card.style.transform='rotateX('+(-y*5)+'deg) rotateY('+(x*7)+'deg) translateY(-2px)';});card.addEventListener('pointerleave',function(){card.style.transform='';});})();
(function () {
  var button = document.getElementById('notificationsMenu');
  if (!button) return;
  var dropdown = button.closest('.topbar-notifications');
  if (!dropdown) return;
  dropdown.addEventListener('shown.bs.dropdown', function () {
    var badge = document.getElementById('notificationCount');
    if (!badge) return;
    fetch(button.dataset.readUrl, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': button.dataset.csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({ csrf_token: button.dataset.csrfToken })
    }).then(function (response) {
      if (!response.ok) throw new Error('Could not mark notifications read');
      return response.json();
    }).then(function (result) {
      if (!result.ok) return;
      badge.remove();
      document.querySelectorAll('.notification-item.is-unread').forEach(function (item) {
        item.classList.remove('is-unread');
        var marker = item.querySelector('.notification-new');
        if (marker) marker.remove();
      });
    }).catch(function (error) { console.error(error); });
  });
})();
</script></body></html>
