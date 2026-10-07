// OneSignalSDKWorker.js
// OneSignal exige este nombre de archivo exacto para su service worker
// (ignora cualquier otro nombre que le pases, aunque se lo configures
// distinto en el init() — es una limitación conocida de su SDK).
//
// Vive en su propio "scope" separado (ver serviceWorkerParam en
// includes/header_panel.php), así que nunca compite ni se pisa con nuestro
// propio sw.js — cada uno controla su carril sin estorbarse.
importScripts('https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.sw.js');
