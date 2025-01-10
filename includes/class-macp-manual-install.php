<?php
class MACP_Manual_Install {
    public static function force_install() {
        MACP_Debug::log('Starting manual installation');
        MACP_Installer::install();
        MACP_Debug::log('Manual installation completed');
    }
}