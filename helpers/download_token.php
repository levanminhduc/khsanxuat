<?php
// Set the fileDownloadToken cookie right before streaming a file so the
// client-side overlay (data-loading-download) knows the download has started
// and can hide itself. Client passes its token via ?download_token=...
// Standard technique: John Culviner jQuery.fileDownload / Ben Nadel tracking cookie.

if (!function_exists('emitDownloadTokenCookie')) {
    function emitDownloadTokenCookie() {
        if (empty($_GET['download_token'])) {
            return;
        }
        // Whitelist: token is client-generated digits/letters only.
        $token = preg_replace('/[^A-Za-z0-9]/', '', (string)$_GET['download_token']);
        if ($token === '') {
            return;
        }
        // Session cookie (no expiry), path=/ so JS on any page can read it.
        setcookie('fileDownloadToken', $token, 0, '/');
    }
}
