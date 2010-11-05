<?php
$html_header_sent = false;
$html_footer_sent = false;

#-------------------------------------------------------------------------------
# Resets the state of HTML display. Without resetting, you can only
# call html_header() and html_footer() once, and in the correct order.

function html_reset() {
    global $html_header_sent, $html_footer_sent;
    $html_header_sent = false;
    $html_footer_sent = false;
}

#-------------------------------------------------------------------------------
# Displays the HTML header tags (doctype, <html>, <head>, <title>, etc.)

function html_header($title, $css = NULL, $ie_css = NULL, $jsfile = NULL,
    $body_id = null) {
    global $html_header_sent;
    
    if ($html_header_sent)
        error('HTML headers already sent!');
    $html_header_sent = true;
echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title><?php echo $title ?></title>
    <meta name="AUTHOR" content="Arijit De" />
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<?php
    # Add CSS link if available
    if (isset($css)) {
        echo "\t<link rel=\"stylesheet\" type=\"text/css\" href=\"$css\" />\n";
    }
    
    if (isset($ie_css)) {
?>
    <!--[if IE]>
        <link rel="stylesheet" type="text/css" href="<?php echo $ie_css ?>" />
    <![endif]-->
<?php
    }

    # Add javascript link if available
    $jspart  = "";
    if (isset($jsfile)) {
        echo "\t";
        html_include_js($jsfile);
        $jspart = "onload=\"init()\"";
    }
    echo "</head>";
    
    $idpart = "";
    if (isset($body_id))
        $idpart = "id=\"$body_id\"";
    
    echo "<body $jspart $idpart>";
}

#-------------------------------------------------------------------------------
# Displays the HTML footer tags. All rendering must happen between calling
# html_header() and this function.

function html_footer() {
    global $html_header_sent, $html_footer_sent;
    
    if (!$html_header_sent)
        error('HTML headers not sent before footers.');
    if ($html_footer_sent)
        error('HTML footers already sent.');
    $html_footer_sent = true;

    dump("</body>");
    dump("</html>");
}

#-------------------------------------------------------------------------------
# Escapes special characters like <,> so that they arent eaten up by a browser

function html_escape($str) {
    return preg_replace(array("/&/", "/>/", "/</", "/\"/"), array("&amp;", "&gt;" ,"&lt;", "&quot;"), $str);
}

#-------------------------------------------------------------------------------
# Strips off all html tags

function html_strip($str) {
    return preg_replace("/<([A-Z]+)>(.*)<\/\\1>/", "\\2", $str);
}

#-------------------------------------------------------------------------------
# Link in a Javascript file

function html_include_js($jsfile) {
    echo "<script type=\"text/javascript\" src=\"$jsfile\"></script>\n";
}

#-------------------------------------------------------------------------------
# Draws upper part of a rounded rect

function html_rounded_box_open() {
    ?>
    <div class="solcont">
    <div class="soltop">
    <img src="images/tl.png" alt="" 
    width="20" height="20" class="corner" 
    style="display: none" />
    </div>
    <?php
}

#-------------------------------------------------------------------------------
# Draws lower part of a rounded rect

function html_rounded_box_close() {
    ?>
    <div class="solbottom">
    <img src="images/bl.png" alt=""  
    width="20" height="20" class="corner" 
    style="display: none" />
    </div> 
    </div> 
    <?php
}

#-------------------------------------------------------------------------------
# Shows javascript warning if not found

$html_javascript_checked = false;
function html_javascript_check() {
    global $html_javascript_checked;
    
    if (!$html_javascript_checked) {
        ?>
        <div id="javascript_warn"><p class="lower"><strong>Warning!</strong> Javascript is not enabled on your browser. Many features such as timer display, submitted solution display, automatic problem version tracking, shortcut keys, autosave and smart indentation will not work without it.</p></div>
        <script type="text/javascript">
        getObj('javascript_warn').style.display = "none";
        </script>
        <?php
        
        $html_javascript_checked = true;
    }
}

?>