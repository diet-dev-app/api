# 🚀 Guía Completa de Instalación y Configuración de hyper-mcp

## 📋 Tabla de Contenidos
- [Descripción General](#descripción-general)
- [Requisitos Previos](#requisitos-previos)
- [Método de Instalación: Docker](#método-de-instalación-docker)
- [Configuración en VS Code](#configuración-en-vs-code)
- [Configuración de Plugins](#configuración-de-plugins)
- [Configuración de API Keys](#configuración-de-api-keys)
- [Verificación y Pruebas](#verificación-y-pruebas)
- [Solución de Problemas](#solución-de-problemas)
- [Plugins Disponibles](#plugins-disponibles)
- [Referencias](#referencias)

---

## 📖 Descripción General

**hyper-mcp** es un servidor MCP (Model Context Protocol) rápido y seguro escrito en Rust que extiende las capacidades de IA mediante plugins WebAssembly (WASM). Permite integrar múltiples herramientas y servicios directamente en VS Code para mejorar la experiencia de desarrollo con asistentes de IA.

### Características Principales:
- ✅ **Plugins en WebAssembly**: Escribe plugins en cualquier lenguaje que compile a WASM
- ✅ **Sandboxing Seguro**: Aislamiento de plugins con control granular de permisos
- ✅ **Distribución OCI**: Plugins distribuidos vía registros de contenedores (Docker Hub, GHCR)
- ✅ **Multi-protocolo**: Soporte para `stdio`, `sse` y `streamable-http`
- ✅ **Ligero**: Funciona en entornos con recursos limitados
- ✅ **Verificación de Firmas**: Soporte para Sigstore/Cosign (opcional)

### Repositorio Oficial:
- GitHub: https://github.com/hyper-mcp-rs/hyper-mcp
- Documentación: https://github.com/hyper-mcp-rs/hyper-mcp/blob/main/README.md

---

## 🔧 Requisitos Previos

### Sistema Operativo
- **WSL (Windows Subsystem for Linux)** - Ubuntu 20.04+ o similar
- **Linux** nativo (Ubuntu, Debian, etc.)
- **macOS** 12+ (requiere ajustes menores en rutas)

### Software Requerido

1. **Docker** instalado y funcionando
   ```bash
   # Verificar instalación de Docker
   docker --version
   # Debería mostrar: Docker version 20.10+
   
   # Verificar que Docker está corriendo
   docker ps
   ```

2. **VS Code** con extensiones de GitHub Copilot
   - Visual Studio Code 1.80+
   - GitHub Copilot Extension
   - Model Context Protocol (MCP) support

3. **Acceso a Internet** para descargar imágenes de plugins OCI

### Permisos de Docker

Asegúrate de que tu usuario puede ejecutar Docker sin `sudo`:

```bash
# Agregar usuario al grupo docker (si no está)
sudo usermod -aG docker $USER

# Aplicar cambios (requiere logout/login o reiniciar)
newgrp docker

# Verificar acceso
docker run hello-world
```

---

## 🐳 Método de Instalación: Docker

Usaremos Docker para ejecutar hyper-mcp, lo cual ofrece las siguientes ventajas:
- ✅ No requiere instalación local de Rust o compilación
- ✅ Aislamiento completo del sistema
- ✅ Fácil actualización de versiones
- ✅ Configuración reproducible

### Imagen Docker Oficial

```bash
# Imagen oficial (recomendada)
ghcr.io/hyper-mcp-rs/hyper-mcp:latest

# Alternativa: imagen de sevir (fork mantenido)
ghcr.io/sevir/hyper-mcp:latest
```

**Nota**: En esta guía usamos `ghcr.io/sevir/hyper-mcp:latest` que incluye plugins adicionales pre-compilados.

---

## 📝 Configuración en VS Code

### Paso 1: Crear Estructura de Directorios

En la raíz de tu proyecto, crea la siguiente estructura:

```bash
cd /ruta/a/tu/proyecto

# Crear directorio para configuración MCP
mkdir -p .vscode
mkdir -p .ai

# Verificar estructura
tree -L 2 .vscode .ai
```

Estructura resultante:
```
proyecto/
├── .vscode/
│   └── mcp.json          # Configuración de servidores MCP en VS Code
└── .ai/
    └── hyper-mcp.yaml    # Configuración de plugins hyper-mcp
```

### Paso 2: Configurar `mcp.json`

Crea el archivo `.vscode/mcp.json` con el siguiente contenido:

```json
{
	"servers": {
		"hyper-mcp": {
			"type": "stdio",
			"command": "docker",
			"args": [
				"run",
				"-i",
				"--rm",
				"-e",
				"HYPER_MCP_INSECURE_SKIP_SIGNATURE=true",
				"-e",
				"GOOGLE_API_KEY=TU_GOOGLE_API_KEY_AQUI",
				"-e",
				"GOOGLE_SEARCH_ENGINE_ID=TU_SEARCH_ENGINE_ID_AQUI",
				"-v",
				"${workspaceFolder}/.ai/hyper-mcp.yaml:/config.yaml",
				"ghcr.io/sevir/hyper-mcp:latest",
				"-c",
				"/config.yaml"
			]
		}
	},
	"inputs": [
		{
			"id": "google-api-key",
			"type": "promptString",
			"description": "Google API Key (for Google Search)",
			"password": true
		},
		{
			"id": "google-search-engine-id",
			"type": "promptString",
			"description": "Google Custom Search Engine ID",
			"password": false
		}
	]
}
```

#### Explicación de la Configuración:

**Argumentos de Docker:**
- `run`: Ejecuta un nuevo contenedor
- `-i`: Modo interactivo (necesario para stdio transport)
- `--rm`: Elimina el contenedor al terminar (no deja residuos)
- `-e HYPER_MCP_INSECURE_SKIP_SIGNATURE=true`: **CRÍTICO** - Omite verificación de firmas Sigstore (necesario para evitar errores de Rekor)
- `-e GOOGLE_API_KEY=...`: Variable de entorno para API key de Google
- `-e GOOGLE_SEARCH_ENGINE_ID=...`: Variable de entorno para Search Engine ID
- `-v ${workspaceFolder}/.ai/hyper-mcp.yaml:/config.yaml`: Monta el archivo de configuración de plugins
- `ghcr.io/sevir/hyper-mcp:latest`: Imagen Docker a usar
- `-c /config.yaml`: Argumento para hyper-mcp indicando ruta del archivo de configuración

**Variables de Entorno Importantes:**

| Variable | Propósito | Obligatorio |
|----------|-----------|-------------|
| `HYPER_MCP_INSECURE_SKIP_SIGNATURE` | Omite verificación de firmas Sigstore | **SÍ** (para evitar errores) |
| `GOOGLE_API_KEY` | API key de Google Custom Search | Solo si usas plugin de Google |
| `GOOGLE_SEARCH_ENGINE_ID` | ID del motor de búsqueda personalizado | Solo si usas plugin de Google |

### Paso 3: Configurar `hyper-mcp.yaml`

Crea el archivo `.ai/hyper-mcp.yaml` con la configuración de plugins:

```yaml
plugins:
  # Plugin de tiempo - sin API key requerida
  time:
    url: oci://ghcr.io/tuananh/time-plugin:latest

  # Plugin de hash - sin API key requerida
  hash:
    url: oci://ghcr.io/tuananh/hash-plugin:latest

  # Plugin de fetch - obtener contenido web
  fetch:
    url: oci://ghcr.io/tuananh/fetch-plugin:latest
    runtime_config:
      allowed_hosts:
        - "*"  # Permite acceso a cualquier host (ajustar según necesidad)
      memory_limit: "100 MB"

  # Plugin de Context7 - documentación de librerías
  context7:
    url: oci://ghcr.io/tuananh/context7-plugin:nightly
    runtime_config:
      allowed_hosts:
        - context7.com

  # Plugin de pensamiento secuencial
  sequentialthinking:
    url: oci://ghcr.io/sevir/sequentialthinking-plugin:latest

  # Plugin de búsqueda Google - REQUIERE API KEY
  google:
    url: oci://ghcr.io/sevir/hyper-mcp/plugin-google-search:latest
    runtime_config:
      env_vars:
        GOOGLE_API_KEY: "${GOOGLE_API_KEY}"
        GOOGLE_SEARCH_ENGINE_ID: "${GOOGLE_SEARCH_ENGINE_ID}"
      allowed_hosts:
        - www.googleapis.com
        - customsearch.googleapis.com

  # Plugin de Perplexity - REQUIERE API KEY (comentado por defecto)
  # perplexity:
  #   url: oci://ghcr.io/sevir/hyper-mcp/plugin-perplexity-search:latest
  #   runtime_config:
  #     env_vars:
  #       PERPLEXITY_API_KEY: "${PERPLEXITY_API_KEY}"
  #     allowed_hosts:
  #       - api.perplexity.ai

  # Plugin de Brave Search - REQUIERE API KEY (comentado por defecto)
  # brave:
  #   url: oci://ghcr.io/sevir/hyper-mcp/plugin-brave-search:latest
  #   runtime_config:
  #     env_vars:
  #       BRAVE_API_KEY: "${BRAVE_API_KEY}"
  #     allowed_hosts:
  #       - api.search.brave.com
```

#### Explicación de Runtime Config:

**`allowed_hosts`**: Lista de hosts a los que el plugin puede acceder
- `"*"`: Permite acceso a cualquier host (usar con precaución)
- Lista específica: `["example.com", "api.example.com"]` - Solo esos hosts

**`memory_limit`**: Límite de memoria para el plugin
- Formato: `"100 MB"`, `"512 MB"`, `"1 GB"`
- Protege contra uso excesivo de memoria

**`env_vars`**: Variables de entorno específicas del plugin
- Usa sintaxis `${VARIABLE_NAME}` para referencias
- Las variables deben pasarse desde `mcp.json` con `-e`

---

## 🔑 Configuración de API Keys

### Google Custom Search API

**Paso 1: Obtener Google API Key**

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Habilita **Custom Search JSON API**:
   - Navega a "APIs & Services" > "Library"
   - Busca "Custom Search API"
   - Haz clic en "Enable"
4. Crea credenciales:
   - "APIs & Services" > "Credentials"
   - "Create Credentials" > "API Key"
   - Copia la API key generada

**Paso 2: Crear Custom Search Engine**

1. Ve a [Programmable Search Engine](https://programmablesearchengine.google.com/)
2. Haz clic en "Add" (Añadir)
3. Configura:
   - **Sites to search**: `*` (buscar en toda la web) o dominios específicos
   - **Name**: Dale un nombre descriptivo
4. Haz clic en "Create"
5. En la página de configuración, copia el **Search Engine ID** (cx)

**Paso 3: Actualizar mcp.json**

Reemplaza los placeholders en `.vscode/mcp.json`:

```json
"-e",
"GOOGLE_API_KEY=TU_GOOGLE_API_KEY_AQUI",
"-e",
"GOOGLE_SEARCH_ENGINE_ID=TU_SEARCH_ENGINE_ID_AQUI",
```

### Otras API Keys (Opcional)

**Perplexity AI**: https://www.perplexity.ai/settings/api
- Precio: Desde $0.005 por 1K tokens
- Buena alternativa a Google Search con respuestas AI

**Brave Search**: https://brave.com/search/api/
- 2,000 búsquedas gratis al mes
- Sin tarjeta de crédito requerida para plan gratuito

---

## ✅ Verificación y Pruebas

### Paso 1: Reiniciar VS Code

1. Cierra VS Code completamente
2. Vuelve a abrir el proyecto
3. O presiona `Ctrl+Shift+P` y selecciona **"Developer: Reload Window"**

### Paso 2: Verificar Conexión MCP

1. Abre la paleta de comandos: `Ctrl+Shift+P`
2. Busca: **"MCP: Show Server Status"** o similar
3. Deberías ver `hyper-mcp` conectado

**Logs esperados** (Output panel):
```
[info] Starting MCP server: hyper-mcp
[warning] [server stderr] 2026-02-11T09:33:36.760917Z INFO hyper_mcp::oci: Using Sigstore TUF data for verification
[info] Connection state: Connected
```

### Paso 3: Probar Plugins Básicos

Abre el chat de GitHub Copilot y prueba los siguientes comandos:

**1. Plugin de Tiempo:**
```
¿Qué hora es en UTC?
```
Debería responder con la hora actual en formato RFC2822.

**2. Plugin de Hash:**
```
Genera un hash SHA256 de "test hyper-mcp"
```
Debería devolver: `2d01eb0dfaac97e5412a41a75c0e35be3b5cc8eb3aa7dbfa9d7fb281f51de66c`

**3. Plugin de Google Search (si configuraste API key):**
```
Busca en Google: "Ionic Angular best practices"
```
Debería devolver resultados de búsqueda con títulos, URLs y snippets.

**4. Plugin de Fetch:**
```
Obtén el contenido de https://example.com
```
Debería devolver el HTML de la página.

### Paso 4: Verificar Logs de Docker

En otra terminal, verifica que el contenedor se esté ejecutando:

```bash
# Listar contenedores activos con hyper-mcp
docker ps | grep hyper-mcp

# Ver logs del contenedor (reemplaza CONTAINER_ID)
docker logs <CONTAINER_ID>
```

---

## 🔧 Solución de Problemas

### Error: "Image signature verification failed"

**Síntoma:**
```
2026-02-11T09:33:38.188244Z ERROR hyper_mcp::plugins: Error pulling oci plugin: 
Image signature verification failed: Failed to set up trust repository: 
Did not find exactly 1 active Rekor key
```

**Solución:**
Asegúrate de tener la variable de entorno `HYPER_MCP_INSECURE_SKIP_SIGNATURE=true` en los argumentos de Docker:

```json
"args": [
    "run",
    "-i",
    "--rm",
    "-e",
    "HYPER_MCP_INSECURE_SKIP_SIGNATURE=true",  // ✅ DEBE ESTAR AQUÍ
    // ... resto de args
]
```

### Error: "API key not valid"

**Síntoma:**
```json
{
  "error": {
    "code": 400,
    "message": "API key not valid. Please pass a valid API key."
  }
}
```

**Soluciones:**

1. **Verifica que la API key esté correctamente configurada** en `mcp.json`
2. **Asegúrate de que Custom Search API esté habilitada** en Google Cloud Console
3. **Verifica que la API key tenga permisos** para Custom Search API
4. **Regenera la API key** si es muy antigua o ha sido restringida
5. **Revisa límites de cuota** en Google Cloud Console

### Error: "Cannot connect to Docker daemon"

**Síntoma:**
```
Error: Cannot connect to the Docker daemon at unix:///var/run/docker.sock. 
Is the docker daemon running?
```

**Soluciones:**

**En WSL:**
```bash
# Iniciar Docker (si está instalado en WSL)
sudo service docker start

# O usar Docker Desktop (recomendado para WSL)
# Asegúrate de que Docker Desktop está corriendo en Windows
# y tiene habilitada la integración con WSL
```

**Verificar integración WSL en Docker Desktop:**
1. Abre Docker Desktop en Windows
2. Settings > Resources > WSL Integration
3. Habilita tu distribución WSL (Ubuntu, etc.)
4. Apply & Restart

### Error: "config.yaml not found"

**Síntoma:**
```
Error: config file not found: /config.yaml
```

**Soluciones:**

1. **Verifica que `.ai/hyper-mcp.yaml` existe**:
   ```bash
   ls -la .ai/hyper-mcp.yaml
   ```

2. **Verifica el mount de volumen** en `mcp.json`:
   ```json
   "-v",
   "${workspaceFolder}/.ai/hyper-mcp.yaml:/config.yaml",
   ```

3. **Asegúrate de que VS Code tiene el workspace correcto abierto**

### Plugins no se cargan

**Síntoma:**
Los comandos del chat no usan los plugins de hyper-mcp.

**Soluciones:**

1. **Verifica los logs del servidor**:
   - Output panel en VS Code
   - Busca "hyper-mcp" en el selector de output

2. **Verifica que los plugins se descargaron**:
   ```bash
   docker logs $(docker ps | grep hyper-mcp | awk '{print $1}')
   ```

3. **Prueba con plugins sin API key primero** (time, hash)

4. **Reinicia el servidor MCP**:
   - `Ctrl+Shift+P` → "MCP: Restart Server"

### VS Code no encuentra el comando MCP

**Solución:**

1. Verifica que tienes instalado **GitHub Copilot Chat Extension**
2. Actualiza VS Code a la versión más reciente
3. Verifica que el MCP support esté habilitado en la configuración de Copilot

---

## 🔌 Plugins Disponibles

### Plugins Incluidos en esta Configuración

| Plugin | Descripción | API Key Requerida |
|--------|-------------|-------------------|
| **time** | Obtener hora UTC y cálculos de tiempo | ❌ No |
| **hash** | Generar hashes (SHA256, MD5, base64, etc.) | ❌ No |
| **fetch** | Obtener contenido de páginas web | ❌ No |
| **context7** | Buscar documentación de librerías | ❌ No |
| **sequentialthinking** | Pensamiento secuencial y razonamiento | ❌ No |
| **google** | Búsqueda Google Custom Search | ✅ Sí |
| **perplexity** | Búsqueda con IA Perplexity | ✅ Sí (comentado) |
| **brave** | Búsqueda Brave Search | ✅ Sí (comentado) |

### Plugins Adicionales Disponibles (Oficiales)

Para agregar más plugins, consulta la lista completa en:
https://github.com/hyper-mcp-rs/hyper-mcp#available-plugins

**Ejemplos populares:**

- **fs**: Operaciones del sistema de archivos
- **github**: Integración con GitHub API
- **sqlite**: Interactuar con bases de datos SQLite
- **memory**: Almacenar y recuperar memoria con SQLite
- **eval_py**: Ejecutar código Python con RustPython
- **arxiv**: Buscar y descargar papers científicos
- **crypto_price**: Precios de criptomonedas
- **qr_code**: Generar códigos QR

**Formato para agregar plugins:**
```yaml
nombre_plugin:
  url: oci://ghcr.io/hyper-mcp-rs/nombre-plugin:latest
  runtime_config:
    allowed_hosts:
      - host1.com
      - host2.com
    memory_limit: "100 MB"
    env_vars:
      API_KEY: "${API_KEY_NAME}"
```

---

## 📚 Referencias

### Documentación Oficial
- **GitHub Repository**: https://github.com/hyper-mcp-rs/hyper-mcp
- **Runtime Configuration Guide**: https://github.com/hyper-mcp-rs/hyper-mcp/blob/main/RUNTIME_CONFIG.md
- **Creating Plugins**: https://github.com/hyper-mcp-rs/hyper-mcp/blob/main/CREATING_PLUGINS.md
- **Plugin Templates**: https://github.com/hyper-mcp-rs/hyper-mcp/blob/main/TEMPLATES.md
- **Skip Tools Guide**: https://github.com/hyper-mcp-rs/hyper-mcp/blob/main/SKIP_TOOLS_GUIDE.md

### Model Context Protocol (MCP)
- **MCP Specification**: https://modelcontextprotocol.io/
- **MCP Clients**: https://modelcontextprotocol.io/clients

### API Services
- **Google Cloud Console**: https://console.cloud.google.com/
- **Google Programmable Search**: https://programmablesearchengine.google.com/
- **Perplexity AI**: https://www.perplexity.ai/settings/api
- **Brave Search API**: https://brave.com/search/api/

### Docker
- **Docker Documentation**: https://docs.docker.com/
- **Docker Desktop + WSL**: https://docs.docker.com/desktop/wsl/

---

## 📋 Checklist de Instalación Rápida

Usa este checklist para una instalación paso a paso:

- [ ] Docker instalado y funcionando
- [ ] VS Code instalado con GitHub Copilot
- [ ] Crear directorio `.vscode/` en el proyecto
- [ ] Crear directorio `.ai/` en el proyecto
- [ ] Crear archivo `.vscode/mcp.json` con configuración
- [ ] Crear archivo `.ai/hyper-mcp.yaml` con plugins
- [ ] Obtener Google API Key (opcional pero recomendado)
- [ ] Crear Google Custom Search Engine ID (opcional)
- [ ] Actualizar API keys en `mcp.json`
- [ ] Agregar `.vscode/mcp.json` a `.gitignore` (seguridad)
- [ ] Reiniciar VS Code
- [ ] Verificar logs en Output panel
- [ ] Probar plugin de tiempo en chat
- [ ] Probar plugin de hash en chat
- [ ] Probar búsqueda de Google (si configuraste API key)
- [ ] ✅ ¡hyper-mcp funcionando!

---

## 🔐 Seguridad y Mejores Prácticas

### 1. Protección de API Keys

**❌ NUNCA commitees `mcp.json` con API keys al repositorio Git**

Agrega a `.gitignore`:
```gitignore
# MCP configuration with secrets
.vscode/mcp.json

# Alternative: allow mcp.json but with template placeholders
# Then create mcp.local.json for local config
```

### 2. Uso de Variables de Entorno

Para proyectos en equipo, considera usar un archivo de template:

**.vscode/mcp.json.template** (commitear):
```json
{
	"servers": {
		"hyper-mcp": {
			"type": "stdio",
			"command": "docker",
			"args": [
				"run",
				"-i",
				"--rm",
				"-e",
				"HYPER_MCP_INSECURE_SKIP_SIGNATURE=true",
				"-e",
				"GOOGLE_API_KEY=${input:google-api-key}",
				"-e",
				"GOOGLE_SEARCH_ENGINE_ID=${input:google-search-engine-id}",
				"-v",
				"${workspaceFolder}/.ai/hyper-mcp.yaml:/config.yaml",
				"ghcr.io/sevir/hyper-mcp:latest",
				"-c",
				"/config.yaml"
			]
		}
	}
}
```

Cada desarrollador crea su propio `mcp.json` con sus credenciales.

### 3. Restricción de Hosts

Evita usar `"*"` en `allowed_hosts` para producción:

```yaml
# ❌ Inseguro para producción
fetch:
  runtime_config:
    allowed_hosts:
      - "*"

# ✅ Mejor práctica
fetch:
  runtime_config:
    allowed_hosts:
      - "api.github.com"
      - "api.example.com"
      - "trusted-domain.com"
```

### 4. Límites de Memoria

Siempre define `memory_limit` para plugins:

```yaml
fetch:
  runtime_config:
    memory_limit: "100 MB"  # ✅ Previene uso excesivo
```

### 5. Auditoría de Plugins

Antes de usar un plugin, verifica:
- ✅ Fuente oficial o confiable
- ✅ Última actualización reciente
- ✅ Permisos que solicita
- ✅ Documentación disponible

---

## 🆘 Soporte y Comunidad

### Reportar Issues
- GitHub Issues: https://github.com/hyper-mcp-rs/hyper-mcp/issues

### Discusiones
- GitHub Discussions: https://github.com/hyper-mcp-rs/hyper-mcp/discussions

### Contribuir
- Contributing Guide: https://github.com/hyper-mcp-rs/hyper-mcp/blob/main/CONTRIBUTING.md

---

## 📝 Notas Finales

- Esta guía está basada en **hyper-mcp v0.2.3** (última versión al 11 Feb 2026)
- Configuración probada en **WSL Ubuntu 24.04** con **VS Code 1.95+**
- Usa **Docker** para facilitar instalación y mantenimiento
- Los plugins se descargan automáticamente al iniciar hyper-mcp
- El flag `HYPER_MCP_INSECURE_SKIP_SIGNATURE=true` es **necesario** actualmente debido a problemas con Rekor keys

---

**✅ Con esta guía deberías tener hyper-mcp funcionando correctamente en cualquier proyecto nuevo.**

¿Alguna duda? Revisa la sección de [Solución de Problemas](#solución-de-problemas) o consulta la documentación oficial.

---

**Autor**: Guía basada en configuración funcional de Proyecto Saiyan  
**Fecha**: Febrero 2026  
**Versión**: 1.0
