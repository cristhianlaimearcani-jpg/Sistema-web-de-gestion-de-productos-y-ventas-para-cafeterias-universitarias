#!/usr/bin/env bash
set -u

mensajes=(
"feat(productos): agregar registro de productos"
"feat(productos): implementar edicion de productos"
"feat(productos): agregar eliminacion de productos"
"feat(categorias): implementar clasificacion"
"feat(carrito): agregar productos al carrito"
"fix(carrito): corregir calculo de totales"
"feat(ventas): implementar registro de ventas"
"fix(ventas): corregir validacion de cantidades"
"feat(qr): implementar generacion de codigo QR"
"fix(qr): corregir validacion de pagos"
"refactor(pagos): reorganizar logica de transacciones"
"feat(pedidos): agregar estados de pedidos"
"fix(pedidos): corregir actualizacion de estados"
"feat(cupones): implementar descuentos"
"fix(cupones): corregir validacion de cupones"
"feat(facturas): implementar comprobantes"
"feat(auth): agregar registro de usuarios"
"fix(auth): corregir validacion de credenciales"
"refactor(auth): separar responsabilidades"
"feat(admin): agregar panel administrativo"
"style(admin): mejorar diseno del panel"
"style(productos): mejorar tarjetas del catalogo"
"style(carrito): ajustar interfaz de compras"
"perf(database): optimizar consulta de productos"
"refactor(ventas): simplificar calculo de totales"
"test(auth): agregar pruebas de autenticacion"
"test(pagos): agregar pruebas de transacciones"
"build(vite): actualizar configuracion frontend"
"refactor(pedidos): reorganizar controlador"
"fix(ui): corregir visualizacion en moviles"
)

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo 'Error: ejecuta el script dentro de tu repositorio Git.'
  exit 1
fi

if [[ -n "$(git diff --cached --name-only)" ]]; then
  echo 'Hay archivos preparados previamente. Revisa git status y haz commit o quita del stage antes de continuar.'
  exit 1
fi

printf 'Repositorio: %s\n' "$(git rev-parse --show-toplevel)"
echo 'Elige cada mensaje SOLO si hiciste ese cambio real. El script no crea commits vacios.'
for i in "${!mensajes[@]}"; do
  echo
  printf '[%d/%d] %s\n' "$((i+1))" "${#mensajes[@]}" "${mensajes[i]}"
  git status --short
  read -r -p '¿Registrar cambios con este mensaje? [s/N/q]: ' respuesta
  case "$respuesta" in
    s|S)
      if [[ -z "$(git status --porcelain)" ]]; then
        echo 'No hay cambios para registrar.'
        continue
      fi
      git add -A
      if git diff --cached --quiet; then
        echo 'No hay cambios preparados.'
      else
        git commit -m "${mensajes[i]}" || exit 1
      fi
      ;;
    q|Q) echo 'Proceso detenido.'; exit 0 ;;
    *) echo 'Omitido.' ;;
  esac
done
echo
 echo 'Listo. Comprueba los commits con: git log --oneline -30'
echo 'Cuando quieras subirlos: git push origin main'
