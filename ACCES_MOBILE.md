# 📱 Guide d'accès depuis votre téléphone

## ✅ Configuration effectuée

### IP de votre ordinateur WiFi
```
192.168.100.83
```

### URLs à utiliser depuis votre téléphone

1. **Page principale des tickets :**
   ```
   http://192.168.100.83/projet_web/view/front/my_tickets.php
   ```

2. **Régénérer les QR codes :**
   ```
   http://192.168.100.83/projet_web/regenerate_qr.php
   ```

3. **Scanner QR :**
   ```
   http://192.168.100.83/projet_web/view/front/scan_ticket.php
   ```

## 📝 Étapes à suivre

### 1️⃣ Régénérer les QR codes (sur PC)
Ouvrez dans votre navigateur :
```
http://localhost/projet_web/regenerate_qr.php
```
Cela va recréer tous les QR codes avec la nouvelle IP `192.168.100.83`

### 2️⃣ Vérifier la connexion depuis le téléphone
1. Assurez-vous que votre téléphone est sur le **même WiFi** que votre PC
2. Ouvrez le navigateur de votre téléphone
3. Tapez l'URL : `http://192.168.100.83/projet_web/view/front/my_tickets.php`
4. Vous devriez voir la page des tickets

### 3️⃣ Scanner un QR code
**Option A - Scanner depuis my_tickets.php :**
1. Sur votre téléphone, allez sur : `http://192.168.100.83/projet_web/view/front/my_tickets.php`
2. Cliquez sur le bouton QR code en bas à droite
3. Autorisez l'accès à la caméra
4. Scannez un QR code affiché sur votre écran PC

**Option B - Scanner depuis scan_ticket.php :**
1. Sur votre téléphone, allez sur : `http://192.168.100.83/projet_web/view/front/scan_ticket.php`
2. Cliquez sur "Start Camera Scanner"
3. Scannez le QR code

## 🔧 Dépannage

### ❌ "Site inaccessible" depuis le téléphone

**Solution 1 - Vérifier le pare-feu Windows :**
```powershell
# Exécuter en tant qu'administrateur dans PowerShell
New-NetFirewallRule -DisplayName "Apache HTTP" -Direction Inbound -LocalPort 80 -Protocol TCP -Action Allow
```

**Solution 2 - Vérifier que vous êtes sur le même réseau :**
- Sur PC : Ouvrir cmd et taper `ipconfig`
- Sur téléphone : Vérifier les paramètres WiFi
- Les deux doivent être sur `192.168.100.x`

**Solution 3 - Désactiver temporairement le pare-feu :**
- Panneau de configuration → Pare-feu Windows
- Désactiver le pare-feu (réseau privé uniquement)

### ❌ Le QR code ne scanne pas

**Vérifications :**
1. Les QR codes ont été régénérés avec `regenerate_qr.php` ?
2. L'accès caméra est autorisé dans le navigateur du téléphone ?
3. Le QR code est bien visible et net ?

### ❌ "Invalid ticket parameters" après scan

**Cause :** Les anciens QR codes utilisent l'ancienne IP

**Solution :** Régénérer les QR codes via :
```
http://localhost/projet_web/regenerate_qr.php
```

## 🎯 Test rapide

### Sur PC :
```
http://localhost/projet_web/view/front/my_tickets.php
```

### Sur téléphone :
```
http://192.168.100.83/projet_web/view/front/my_tickets.php
```

Les deux devraient afficher la même page !

## 📊 Vérifier l'IP actuelle

Si l'IP `192.168.100.83` ne fonctionne pas, votre IP WiFi a peut-être changé.

**Sur PC, exécuter :**
```cmd
ipconfig
```

Cherchez la section "Carte réseau sans fil Wi-Fi" et notez l'adresse IPv4.
Ensuite, modifiez le fichier :
```
config/site_config.php
```
Ligne 13, changez l'IP avec la nouvelle.

## ⚡ Changement automatique d'IP

Si votre IP change souvent, modifiez `config/site_config.php` ligne 10-13 :

```php
// Option auto (détecter l'IP automatiquement)
$localIP = getHostByName(getHostName());
define('SERVER_IP', $localIP);
```

Au lieu de :
```php
define('SERVER_IP', '192.168.100.83');
```
