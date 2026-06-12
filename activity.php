<?php
// 90-Day Activity moved into the Customer Results report (a tab there).
// Keep this route working by redirecting any old links.
header('Location: /customer-results', true, 301);
exit;
