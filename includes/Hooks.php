<?php

use MediaWiki\MediaWikiServices;

class YandexMetricaHooks {
  public static function onBeforePageDisplay(OutputPage $out, Skin $sk): void {
    MWDebug::init();

    $PREFIX = 'YandexMetrica';
    $config = $out->getConfig();

    $cnf_id = $config->get($PREFIX . 'Id');
    $cnf = [
      'accurateTrackBounce' => $config->get($PREFIX . 'TrackBounce'),
      'clickmap' => $config->get($PREFIX . 'WatchClicks'),
      'sendTitle' => $config->get($PREFIX . 'TrackTitles'),
      'trackHash' => $config->get($PREFIX . 'TrackHashes'),
      'trackLinks' => $config->get($PREFIX . 'TrackLinks'),
      'webvisor' => $config->get($PREFIX . 'UseWebvisor')
    ];

    if (empty($cnf_id) || $cnf_id === 0) {
      MWDebug::log('YM exited: no ID');
      return;
    }

    $allowed = $out->getAllowedModules(ResourceLoaderModule::TYPE_SCRIPTS);
    if ($allowed < ResourceLoaderModule::ORIGIN_USER_SITEWIDE) {
      MWDebug::log('YM exited: forbidden page');
      return;
    }

    $user = $out->getUser();
    $rights = MediaWikiServices::getInstance()->getPermissionManager()->getUserPermissions($user);
    if (in_array('suppress-yametrica', $rights, true)) {
      MWDebug::log('YM exited: suppression');
      return;
    }

    $init_params = json_encode($cnf);
    $out->addHeadItem('yandex-metrica', <<<EOD
      <!-- Yandex.Metrika counter -->
      <script type="text/javascript" >
        (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
        (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

        ym($cnf_id, "init", $init_params);
      </script>
      <noscript><div><img src="https://mc.yandex.ru/watch/$cnf_id" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
      <!-- /Yandex.Metrika counter -->
    EOD);
  }
}
