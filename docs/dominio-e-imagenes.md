# Dominio propio + imágenes públicas

Guía de lo que hay que hacer **fuera del código** para que las fotos de artículos
tengan una URL HTTPS que se pueda compartir (Mercado Libre, catálogo, redes).

El código ya está listo en la rama `servidor-url-img`: carpeta privada y carpeta
pública, token por archivo, ruta `/img/{token}` y botón «Publicar» en la ficha del
artículo. Lo único que falta es el nombre y el certificado.

---

## 0 · Qué se va a usar

| Nombre | Para qué | Quién entra |
|---|---|---|
| `panel.tudominio.com` | el panel administrativo | solo la oficina (o VPN) |
| `media.tudominio.com` | **solo** `/img/…` | cualquiera con el enlace |

---

## 1 · Comprar el dominio

Opciones reales (precios de noviembre 2026, verificar en el carrito):

| Registrador | `.com` primer año | Renovación | Por qué |
|---|---|---|---|
| **Cloudflare Registrar** | ~$11.17 | ~$11.17 | vende a costo, sin sobreprecio, y el DNS ya queda en Cloudflare (habilita el túnel gratis) |
| Porkbun | ~$11.81 | ~$11.81 | panel más amable, cualquier DNS |

Los TLD de oferta (`.site`, `.online`, `.shop` a ~$1 el primer año) **no conviene**:
la renovación salta a $27–43.

**Recomendación:** Cloudflare Registrar, un `.com` de ~$11 al año. Se registra
directo desde el panel de Cloudflare (Register domains), sin transferencias.

> Con Cloudflare Registrar los nameservers son de Cloudflare y no se pueden cambiar.
> No es un problema: es justamente lo que permite el túnel del paso 3.

---

## 2 · Crear los dos nombres (DNS)

En el panel de Cloudflare, zona del dominio:

| Tipo | Nombre | Contenido | Proxy |
|---|---|---|---|
| A | `panel` | IP pública del servidor | naranja (proxied) |
| A | `media` | IP pública del servidor | naranja (proxied) |

Si el servidor **no tiene IP pública** (está detrás del router), no se crean estos
registros: se usa el túnel del paso 3 directamente.

---

## 3 · Exponer `media` de una de las dos formas

### 3.a · Con IP pública y puertos 80/443 abiertos

Certificado gratuito en la VM:

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d media.tudominio.com -d panel.tudominio.com
```

Certbot instala el certificado y lo renueva solo. Válido 90 días, renovación
automática; no hay que tocar nada más.

### 3.b · Sin IP pública (recomendado en el taller)

Cloudflare Tunnel: el servidor abre una conexión **de salida**; no se abre ningún
puerto del router.

```bash
curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb -o cloudflared.deb
sudo dpkg -i cloudflared.deb
cloudflared tunnel login
cloudflared tunnel create quantum-media
cloudflared tunnel route dns quantum-media media.tudominio.com
```

`/etc/cloudflared/config.yml`:

```yaml
tunnel: quantum-media
credentials-file: /root/.cloudflared/<id>.json
ingress:
  - hostname: media.tudominio.com
    path: ^/img/.*
    service: http://localhost:80
  - hostname: media.tudominio.com
    service: http_status:403
  - service: http_status:404
```

`sudo cloudflared service install && sudo systemctl enable --now cloudflared`

Ese `http_status:403` es la privacidad: por `media.tudominio.com` **solo** pasa
`/img/`; cualquier otra ruta se rechaza antes de llegar a Apache.

---

## 4 · Apache: el bloque de las imágenes

`/etc/apache2/sites-available/media.conf`:

```apache
<VirtualHost *:80>
    ServerName media.tudominio.com
    DocumentRoot /var/www/html/public

    # Solo las imágenes publicadas salen por este nombre.
    <LocationMatch "^/img/">
        Require all granted
    </LocationMatch>

    <Location "/">
        Require all denied
    </Location>
</VirtualHost>
```

```bash
sudo a2ensite media && sudo systemctl reload apache2
```

Con esto, `media.tudominio.com/` responde **403** y `media.tudominio.com/admin`
también: el panel no es alcanzable por ese nombre.

---

## 5 · Configurar la aplicación

En el `.env` de la VM:

```env
APP_URL=https://panel.tudominio.com
MEDIA_URL=https://media.tudominio.com
```

```bash
php artisan optimize:clear
```

`MEDIA_URL` es lo único que hace que las URLs publicadas salgan con el nombre de las
imágenes. Si se deja vacío, salen con `APP_URL` (sirve para probar en local).

---

## 6 · Probar

1. Panel → Inventario → Artículos → abrir uno → pestaña **Fotos y archivos**.
2. Subir una foto.
3. **Publicar** → el aviso muestra la URL.
4. Abrirla en una ventana privada (sin sesión): debe verse la foto.
5. **Dejar de publicar** → la misma URL debe dar 404.

---

## 7 · Lo que se guarda y dónde

| Carpeta | Contenido | Acceso |
|---|---|---|
| `storage/app/private/attachments` | todo lo que se sube | solo por el panel, con sesión |
| `storage/app/public/media` | copia de lo publicado | por `/img/{token}`, sin sesión |

Al despublicar, la copia pública se borra y el archivo vuelve a la carpeta privada.
Los adjuntos de **clientes** (comprobantes, identificaciones) no se pueden publicar:
el sistema solo lo permite en artículos, y esa lista está en `config/quantum.php`.

---

## Pendiente

- [ ] Comprar el dominio
- [ ] Crear `media.tudominio.com` (DNS o túnel)
- [ ] Certificado (certbot o túnel)
- [ ] Vhost de Apache
- [ ] `APP_URL` y `MEDIA_URL` en el `.env` de la VM
- [ ] Probar publicar / despublicar
