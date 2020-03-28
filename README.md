<h2>Custom module for Drupal 8.</h2>
<h4>This one are include:</h4>
<ul>
  <li>Queue which send email to users with role Administrator after any node create.</li>
  <li>Form that process all pending items from previous queue in foreground via Batch.</li>
  <li>UnpublishNodeQueue queue and worker that will unpublish nodes.</li>
  <li>Cron that will find 15 most recently updated nodes and put them to UnpublishNodeQueue.</li>
<ul>
