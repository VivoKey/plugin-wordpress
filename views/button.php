<p style="text-align: center;">
- or -
</p>
<br>
<p style="margin-bottom: 20px;">
<button type="button" onclick="location.href='https://api.vivokey.com/openid/authorize?response_type=code&scope=openid&client_id=<?php echo $this->get_option('VivoKey_Client_ID'); ?>&state=auth&redirect_uri=<?php echo get_site_url() ?>'" class="btn" style="width: 100%; background-color: #0f70b7; text-align: center; color: white; padding-top: 6px; padding-bottom: 6px; border-radius: 8px; border: 0px; border-collapse: collapse;">
                <div style="cursor: pointer;">
                <img style="float: left;
                vertical-align: middle;
                margin-left: 10px;
                " src="./wp-content/plugins/VivoKey-Login/vivokey.png" width="32" height="32"/>
                <span style="line-height: 32px; text-align: center; font-size: +1.2em;">Log in with VivoKey</span>
                </div>
</button>
<a style="color: #72777c; font-size:82%;" href="http://vivokey.com/wordpress-plugin-help">Need help with your VivoKey?</a>
</p>