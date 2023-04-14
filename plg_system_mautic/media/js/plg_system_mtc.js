(function () {
	'use strict';

	(function () {
		/* Mautic */
		/* (C) 2023 Mautic */

		function track() {

			const baseurl = Joomla.getOptions('plgmtc.baseurl');
			let attrs = [];
			attrs = Joomla.getOptions('plgmtcOptions');
			// TODO Custom Params not working console.log(attrs);

			(function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;
			w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments);},a=d.createElement(t),
			m=d.getElementsByTagName(t)[0];a.async=1;a.src=u;m.parentNode.insertBefore(a,m);
			})(window,document,'script', baseurl+'/mtc.js','mt');

			mt('send', 'pageview', attrs);

		}

		document.addEventListener("DOMContentLoaded", track);


	})();

})();
//# sourceMappingURL=plg_system_mtc.js.map
