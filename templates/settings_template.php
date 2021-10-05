<link rel="stylesheet" id="wpm-core-css" href="<?= plugins_url('assets/css/styles.css?7', __FILE__) ?>" media="all">

<form action="" method="post">
    <div id="settings-panel">

        <div class="section-company">
            <div class="left-side">
                <ul>
                    <li><a class="change-table active" data-table="settings-table">Main Settings</a></li>
                    <li><a class="support-item" href="https://wp-masters.com" target="_blank">Plugin Support</a></li>
                </ul>
            </div>
            <div class="right-side">
                <a href="https://wp-masters.com" target="_blank"><img src="<?= plugins_url('assets/img/wp_masters_logo.png', __FILE__) ?>" alt=""></a>
            </div>
        </div>

        <div class="select-table" id="settings-table">
            <div class="section_data">
                <div class="title">API Settings</div>
                <div class="head_items">
                    <div class="item-table">AppKeyToken:</div>
                    <div class="item-table">AppKey:</div>
                    <div class="item-table">API URL:</div>
                    <div class="item-table">Supplier:</div>
                    <div class="item-table">ReferenceNo:</div>
                </div>
                <div class="items-list">
                    <div class="item-content">
                        <div class="item-table"><input type="text" name="api_settings[appKeyToken]" value="<?php if(isset($settings) && isset($settings["appKeyToken"])) {echo $settings["appKeyToken"];} ?>"></div>
                        <div class="item-table"><input type="text" name="api_settings[appKey]" value="<?php if(isset($settings) && isset($settings['appKey'])) {echo $settings['appKey'];} ?>"></div>
                        <div class="item-table"><input type="text" name="api_settings[url]" value="<?php if(isset($settings) && isset($settings['url'])) {echo $settings['url'];} ?>"></div>
                        <div class="item-table"><input type="text" name="api_settings[supplier]" value="<?php if(isset($settings) && isset($settings['supplier'])) {echo $settings['supplier'];} ?>"></div>
                        <div class="item-table"><input type="text" name="api_settings[referenceNo]" value="<?php if(isset($settings) && isset($settings['referenceNo'])) {echo $settings['referenceNo'];} ?>"></div>
                    </div>
                </div>
            </div>

            <button class="button button-primary button-large" id="save-settings" type="submit">Save settings</button>
        </div>

    </div>
</form>

<script type="text/javascript" src="<?= plugins_url('assets/js/scripts.js?7', __FILE__) ?>" id="wpm-core-js"></script>