</main>

<footer class="site-footer">
  <div class="footer-inner" style="justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
    <p style="font-size:12px;color:var(--text-muted,#666);">&copy; <?= date('Y') ?> Autura NewCo, LLC. Internal use only.</p>
    <p style="font-size:12px;color:var(--text-muted,#666);">
      <a href="mailto:<?= CONTACT_EMAIL ?>" style="color:inherit;"><?= CONTACT_EMAIL ?></a>
    </p>
  </div>
  <div class="amr-disclaimer">
    <strong>Disclaimer</strong>
    <ul>
      <?php foreach (AMR_DISCLAIMER_POINTS as $pt): ?><li><?= $pt ?></li><?php endforeach; ?>
    </ul>
  </div>
</footer>

<div class="amr-print-disc amr-print-bottom"><?= AMR_DISCLAIMER_SHORT ?></div>

<script src="/assets/js/main.js"></script>
</body>
</html>
