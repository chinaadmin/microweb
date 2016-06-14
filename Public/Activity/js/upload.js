var ngUpload = angular.module('ngUpload', ["ui.router", "tryEat.controllers", "tryEat.services"])
	.run(function() {
		if (!window.previewCamera) {
			window.previewCamera = camera();
		};

	})
