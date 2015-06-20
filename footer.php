<?php global $theme; ?>
    
<?php if($theme->display('footer_widgets')) { ?>
    <div id="footer-widgets" class="clearfix">
        <?php
        /**
        * Footer  Widget Areas. Manage the widgets from: wp-admin -> Appearance -> Widgets 
        */
        ?>
        <div class="footer-widget-box">
            <?php
                if(!dynamic_sidebar('footer_1')) {
                    $theme->hook('footer_1');
                }
            ?>
        </div>
        
        <div class="footer-widget-box">
            <?php
                if(!dynamic_sidebar('footer_2')) {
                    $theme->hook('footer_2');
                }
            ?>
        </div>
        
        <div class="footer-widget-box footer-widget-box-last">
            <?php
                if(!dynamic_sidebar('footer_3')) {
                    $theme->hook('footer_3');
                }
            ?>
        </div>
        
    </div>
<?php  } ?>

    <div id="footer">
    
        <div id="copyrights">
            <?php
                if($theme->display('footer_custom_text')) {
                    $theme->option('footer_custom_text');
                } else { 
                    ?> &copy; <?php echo date('Y'); ?>  <a href="<?php echo home_url(); ?>/"><?php bloginfo('name'); ?></a><?php
                }
            ?> 
        </div>

    </div><!-- #footer -->
    
</div><!-- #container -->

<?php wp_footer(); ?>
<?php $theme->hook('html_after'); ?>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-64306484-1', 'auto');
  ga('send', 'pageview');

</script>
</body>
</html>
