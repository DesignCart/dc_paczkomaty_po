(function ($) {

	// 🔹 Ładowanie SDK InPost (raz)
	function loadInPostWidget(callback) {
		if (typeof window.easyPack === 'undefined') {
			var script = document.createElement('script');
			script.src = 'https://geowidget.easypack24.net/js/sdk-for-javascript.js';
			script.onload = callback;
			document.head.appendChild(script);
		} else {
			callback();
		}
	}

	// 🔹 Funkcja otwierająca mapę InPost
	window.openGeoWidget = function () {
		loadInPostWidget(function () {

			// Usuń poprzedni modal, jeśli istnieje
			$('#locker-modal').remove();

			// HTML modala Bootstrap
			const modalHTML = `
				<div id="locker-modal" class="modal fade" tabindex="-1">
					<div class="modal-dialog modal-lg modal-dialog-centered">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title">Wybierz paczkomat InPost</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
							</div>
							<div class="modal-body" style="height:600px;">
								<div id="easypack-map" style="height:100%;"></div>
							</div>
						</div>
					</div>
				</div>`;
			$('body').append(modalHTML);

			var modal = new bootstrap.Modal(document.getElementById('locker-modal'));
			modal.show();

			// Inicjalizacja mapy InPost
			easyPack.init({
				defaultLocale: 'pl',
				mapType: 'osm',
				points: {
					types: ['parcel_locker']
				},
				map: {
					initialTypes: ['parcel_locker'],
					useGeolocation: true,
					center: [52.2297, 21.0122], // Warszawa jako fallback
					zoom: 6
				}
			});

			setTimeout(function () {
				const map = easyPack.mapWidget('easypack-map', function (point) {
					if (!point) return;

					const code = point.name;
					const address = `${point.address.line1 || ''}, ${point.address.line2 || ''}`;
					const desc = point.location_description || '';

					// AJAX zapis kodu paczkomatu
					$.ajax({
						url: 'index.php?route=checkout/checkout.saveInpostPo',
						type: 'post',
						data: { locker_code: code, locker_address: address, locker_desc: desc },
						dataType: 'json',
						success: function (data) {
							if (data.success) {
								// Dodaj informację obok metody wysyłki
								const $label = $('label[for="input-shipping-method-dc_paczkomaty_po.dc_paczkomaty_po"]');
								$label.find('.text-success').remove();
								$label.append(`<br><small class="text-success">📦 ${code} – ${address}</small>`);

								// Zamknij modal
								const modalEl = document.getElementById('locker-modal');
								const modal = bootstrap.Modal.getInstance(modalEl);
								modal.hide();
							} else {
								alert(data.error || 'Błąd zapisu paczkomatu');
							}
						},
						error: function () {
							alert('Błąd połączenia z serwerem');
						}
					});
				});
			}, 300);

		});
	};

	function injectLockerButton() {
		var $radio = $('input[name="shipping_method"][value="dc_paczkomaty_po.dc_paczkomaty_po"]');
		if (!$radio.length) return;

		$radio.each(function () {
			var $r = $(this);
			if ($r.data('dc-btn-added')) return; 

			var id = $r.attr('id');
			var $label = id ? $('label[for="' + id + '"]') : $();
			var $container = $label.length ? $label : $r.closest('.form-check, .radio, .form-group, div');

			var $btn = $('<div><button type="button" class="btn btn-sm btn-outline-primary mt-1 dc-locker-btn">Wybierz paczkomat</button></div>');
			$btn.on('click', function () {
				window.openGeoWidget();
			});

			if ($label.length) {
				$label.after('<br>');
				$label.after($btn);
			} else {
				$container.append($btn);
			}

			$r.data('dc-btn-added', true);
		});
	}

	$(function () {
		injectLockerButton();
		$(document).on('change', 'input[name="shipping_method"]', injectLockerButton);
		$(document).ajaxComplete(injectLockerButton);
		setInterval(injectLockerButton, 5000);
	});

})(jQuery);


