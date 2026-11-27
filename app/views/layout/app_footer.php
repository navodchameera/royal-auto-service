  </main>
</div>

<!-- Confirmation dialog used by every form that has data-confirm -->
<dialog class="dlg dlg-sm" id="confirmDialog" aria-labelledby="confirmTitle" aria-describedby="confirmText">
  <div class="dh"><h2 id="confirmTitle">Are you sure?</h2></div>
  <div class="db"><p class="m" id="confirmText" style="margin:0"></p></div>
  <div class="df confirm-actions">
    <button class="btn g" type="button" data-confirm-back>Cancel</button>
    <button class="btn" type="button" data-confirm-ok>Continue</button>
  </div>
</dialog>

<script src="<?= BASE_URL ?>/assets/js/auth.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/nav.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/confirm.js" defer></script>
<script src="<?= BASE_URL ?>/assets/js/menu.js" defer></script>
</body>
</html>
