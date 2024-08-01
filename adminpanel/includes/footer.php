
    <div id="message-alert" class="message-confirm">
        <div class="head">
            <div class="close"></div>
        </div>
        <div class="content">
            <div class="message"></div>
            <div class="buttons">
                <input type="button" value="OK" class="button-light close" />
            </div>
        </div>
    </div>
    <div id="message-confirm-delete" class="message-confirm">
        <div class="head">
            <div class="close"></div>
        </div>
        <div class="content">
            <div class="message"></div>
            <div class="buttons">
                <input type="button" value="OK" class="button-light" id="butt-ok" />
                <input type="button" value="<? echo I18n :: locale("cancel"); ?>" class="button-dark close" />
            </div>
        </div>
    </div>
    <div id="footer">
        <div class="footer-title">
            <a href="http://mv-framework.<? echo ($region == "ru") ? "ru" : "com"; ?>" target="_blank">
                <? echo I18n :: locale("mv")." ".number_format(Registry :: getVersion(), 1); ?>
            </a>
        </div>
    </div>  
</div>
</body>
</html>
