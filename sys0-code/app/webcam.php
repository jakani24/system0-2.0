<!DOCTYPE html>
<html>
	<?php
		$username = htmlspecialchars($_GET["username"]);
		$printer_url = $_GET["url"];
		$rotation = $_GET["rotation"];
	?>
	<head>
		<title>Webcam</title>
	</head>
	<body>
		<!-- Display the first image -->
		<a id="image-link" href="/user_files/<?php echo $username; ?>/<?php echo $printer_url; ?>.jpeg" target="_blank">
			<img id="webcam-image" style="transform: rotate(<?php echo $rotation; ?>deg);" width="100%" src="/user_files/<?php echo $username; ?>/<?php echo $printer_url; ?>.jpeg" alt="Webcam Feed">
		</a>
		<script>
			// Function to call PHP script to download the latest image and then swap it
			function loadAndSwapImage() {
				// Make an AJAX call to PHP to trigger the image download
				let xhr = new XMLHttpRequest();
				xhr.open('GET', '/api/download_image.php?username=<?php echo $username; ?>&url=<?php echo $printer_url; ?>', true);
				xhr.onload = function() {
					if (xhr.status === 200) {
						// Preload the new image once the PHP script has completed downloading
						let img = new Image();
						img.src = '/user_files/<?php echo $username; ?>/<?php echo $printer_url; ?>.jpeg?rand=' + Math.random(); // Add cache buster to avoid caching issues

						// Swap the image when it is loaded
						img.onload = function() {
							let webcamImage = document.getElementById('webcam-image');
							webcamImage.src = img.src;
						}
					}
				};
				xhr.send(); // Execute the request
			}

			// Reload the image every 5 seconds
			setInterval(loadAndSwapImage, 5000);
		</script>
	</body>
</html>
