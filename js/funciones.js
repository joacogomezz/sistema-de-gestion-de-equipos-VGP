function validarFormVacio(formulario) {
		var datos = $('#' + formulario).serialize();
		var pares = datos.split('&');
		var vacios = 0;
		for (var i = 0; i < pares.length; i++) {
			var par = pares[i].split('=');
			var valor = (par[1] !== undefined) ? decodeURIComponent(par[1].replace(/\+/g, ' ')) : '';
			if (valor === '' || valor === 'A') {
				vacios++;
			}
		}
		return vacios;
	}

function validarFormVacioConArchivos(formulario) {
		var vacios = 0;
		var form = $('#' + formulario);

		form.find('input[required], select[required], textarea[required]').each(function() {
			var val = $(this).val();
			if (val === '' || val == null) {
				vacios++;
			}
		});

		var fileInput = form.find('input[type="file"][required]');
		if (fileInput.length > 0 && fileInput[0].files.length === 0) {
			vacios++;
		}

		return vacios;
	}

/**
 * Descarga un PDF desde una URL del mismo sitio sin abrir ventana ni pestaña nueva.
 * Usa un iframe oculto; el servidor debe enviar el PDF como adjunto (Content-Disposition).
 */
function descargarPdfResguardo(url) {
	if (!url) {
		return;
	}
	var iframe = document.getElementById('iframeDescargaPdfOculto');
	if (!iframe) {
		iframe = document.createElement('iframe');
		iframe.id = 'iframeDescargaPdfOculto';
		iframe.setAttribute('aria-hidden', 'true');
		iframe.title = 'Descarga de documento';
		iframe.style.cssText = 'position:absolute;width:0;height:0;border:0;visibility:hidden';
		document.body.appendChild(iframe);
	}
	iframe.src = 'about:blank';
	setTimeout(function() {
		iframe.src = url;
	}, 0);
}