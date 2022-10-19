<?php

class PluginTest extends TestCase
{
    public function test_plugin_installed() {
        activate_plugin( 'disciple-tools-team-module/disciple-tools-team-module.php' );

        $this->assertContains(
            'disciple-tools-team-module/disciple-tools-team-module.php',
            get_option( 'active_plugins' )
        );
    }
}
