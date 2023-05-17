(function () {
	'use strict';

	(function () {
		/* Mautic - Martina Scholz */
		/* (C) 2023 Mautic, Martina Scholz, SimplySmart-IT <https://simplysmart-it.de> */


		function sismos_token() {	

			const btnGenToken = document.getElementById('genToken');
			const btnClearToken = document.getElementById('clearToken');
			const adminform = document.querySelector('form[name="adminForm"]');
			const task_id = document.querySelector('input[name="task"]');
			const genToken = document.querySelector('input[name="gentoken"]');
			

			function generateToken() {
				// task is a hidden field in the foot of the form			
				task_id.value = 'plugin.apply';
				genToken.value = '1';
			}	
			
			function clearToken() {
				var inputs = adminform.querySelectorAll('input[name^="jform[params][token]"');
				inputs.forEach((input) => {
					input.value = '';
				});
				var textareas = adminform.querySelectorAll('textarea[name^="jform[params][token]"');
				textareas.forEach((textarea) => {
					// never clear the refresh token
					if (textarea.name !== 'jform[params][token][refresh_token]') {
						textarea.value = '';
						textarea.textContent = '';
					}
				});
				task_id.value = 'plugin.apply';
				genToken.value = '';
			}

			btnGenToken.addEventListener('click', generateToken);
			if (btnClearToken) {
				btnClearToken.addEventListener('click', clearToken);
			}		

			document.removeEventListener("DOMContentLoaded", sismos_token);
		}

		document.addEventListener("DOMContentLoaded", sismos_token);

	})();

})();
//# sourceMappingURL=sismos_token.js.map
