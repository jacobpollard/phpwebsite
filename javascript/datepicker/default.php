<?php
/**
 * @version $Id$
 * @author Matthew McNaney <mcnaney at gmail dot com>
 */

loadDatepicker();
function loadDatepicker() {
    javascript('jquery');
    $source_http = PHPWS_SOURCE_HTTP;
    $script = <<<EOF
<script type="text/javascript">{$source_http}javascript/datepicker/js/bootstrap-datepicker.js</script>
<script type="text/javascript">
$(window).load(function() {
    $('.datepicker').datepicker();
});
</script>
EOF;
\Layout::addJSHeader($script);
}
?>