@echo off
echo ========================================
echo Configuration du Pare-feu pour Apache
echo ========================================
echo.
echo Cette commande necessite les droits administrateur.
echo Clic droit sur ce fichier et "Executer en tant qu'administrateur"
echo.
pause

netsh advfirewall firewall add rule name="Apache HTTP Server" dir=in action=allow protocol=TCP localport=80

echo.
echo ========================================
echo Regle de pare-feu ajoutee avec succes!
echo ========================================
echo.
echo Vous pouvez maintenant acceder au site depuis votre telephone:
echo http://192.168.100.83/projet_web
echo.
pause
