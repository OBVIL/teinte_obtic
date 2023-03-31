@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../oeuvres/teinte/bin/teinte
php "%BIN_TARGET%" %*
