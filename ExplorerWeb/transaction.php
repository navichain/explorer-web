<!DOCTYPE html>
<html lang="en">
<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="ChainUs is a blockchain platform for future.">
	<meta name="keywords" content="chainus, chus, blockchain, platform">
	<meta name="author" content="HoangDN">

	<!-- Favicon -->
	<link rel="icon" type="image/png" href="">

	<!-- Bootstrap & Plugins CSS -->
	<link href="./assets/bootstrap.min.css" rel="stylesheet" type="text/css">
	<link href="./assets/font-awesome.min.css" rel="stylesheet" type="text/css">

	<!-- Custom CSS -->
	<?php
	include "_common.php";
	?>

</head>
<body>

	<?php
	include "_header.php";
	include "_search.php";
	?>

	<section class="block-explorer-section section bg-bottom">
		<div class="container">
			<div class="row">
				<div class="col-lg-12">
					<div class="center-heading">
						<h2 class="section-title">Transaction detail</h2>
					</div>
				</div>
				<div class="offset-lg-3 col-lg-6">
					<div class="center-text">
						<p>Following information is provided by our API service. You can/should cross-check with other explorer if needed.</p>
					</div>
				</div>
			</div>			
			<div class="row m-bottom-70">
				<div class="col-lg-9 col-md-9 col-sm-12">
					<div class="table-responsive">
						<table class="table table-striped table-latests table-detail">
							<tbody>
								<tr>
									<td><strong>TXID</strong></td>
									<td id="hash-value"></td>
								</tr>
								<tr>
									<td><strong>Size</strong></td>
									<td id="size-value"></td>
								</tr>
								<tr>
									<td><strong>Total output</strong></td>
									<td id="output-value"></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<div class="col-lg-3 col-md-3 col-sm-12">
					<div class="qr" id="qrcode">
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-6">
					<h3>Input</h3>
					<table class="table table-striped table-latests table-detail">
						<tbody id="input-list">
						</tbody>
					</table>
				</div>
				<div class="col-lg-6">
					<h3>Output</h3>
					<table class="table table-striped table-latests table-detail">
						<tbody id="output-list">
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</section>


	<?php
	include "_footer.php";
	?>

	<script
		src="http://code.jquery.com/jquery-3.3.1.min.js"
		integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
		crossorigin="anonymous"></script>
	<script type="text/javascript" src="qrcode.min.js"></script>
	<script type="text/javascript">
		var base_url = "http://207.148.77.154:3303";

		function formatDate(date) {
			var d = new Date(date * 1000);
			return [d.getFullYear(), d.getMonth() + 1, d.getDate()].join('-') + " " + [d.getHours(), d.getMinutes()].join(':');
		}

		function getParameter(param) {
			var url_string = window.location.href;
			var url = new URL(url_string);
			var c = url.searchParams.get(param);
			return c;
		}

		function noResult() {
			alert('No result!');
		}

		function searchEnter(ev) {
			if (ev.keyCode == 13) {
				search()
			}
		}

		function search() {
			var term = $('#search-term').val().trim();
			if (term.length > 0) {
				// check for block hash
				$.ajax({
					url: base_url + "/search/" + term,
					type: "get",
					dataType: "json",
					success: function(resp) {
						if (resp.result != undefined) {
							if (resp.result == 'block')
								window.location.href = 'block.php?hash=' + term;
							else if (resp.result == 'transaction')
								window.location.href = 'transaction.php?hash=' + term;
							else if (resp.result == 'address')
								window.location.href = 'address.php?hash=' + term;
							else
								noResult();
						} else {
						}
					},
					error: function(resp) {
						noResult();
					}
				});
			}
		}

		$(document).ready(function(){

			// get latest blocks
			$.ajax({
				url: base_url + "/latest-blocks/1",
				type: "get",
				dataType: "json",
				success: function(resp) {
					var latestBlockHeight = resp[0].height;
					$('#latest-block-number').html(latestBlockHeight);
				}
			});

			$.ajax({
				url: base_url + "/get-transaction/" + getParameter('hash'),
				type: "get",
				dataType: "json",
				success: function(resp) {
					if (resp.txid != undefined) {
						transactionInfo = resp;
						
						var size = transactionInfo.size;
						var hash = transactionInfo.txid;

						$('#hash-value').html(hash);
						$('#size-value').html(size);

						var vInput = transactionInfo.inputTransactions;
						$.each(vInput, function(index, vin){
							if (vin[0] == 'coinbase') {
								// show to table
								$('#input-list').append('<tr><td>Coinbase</td><td>-</td></tr>');
							} else {
								var address = vin[0];
								var value = vin[1];

								// show to table
								$('#input-list').append('<tr><td><a href="address.php?hash='+ vin[0] +'">'+ vin[0] +'</a></td><td>'+ vin[1] +' </td></tr>');
							}
						});

						var vOutput = transactionInfo.outputTransactions;
						var totalOutput = 0;
						$.each(vOutput, function(index, vout){
							var address = vout[0];
							var value = vout[1];
							totalOutput += value;

							// show to table
							$('#output-list').append('<tr><td><a href="address.php?hash='+ address +'">'+ address +'</a></td><td>'+ value +' </td></tr>');
						});

						$('#output-value').html(Number(totalOutput).toFixed(8));

						new QRCode(document.getElementById("qrcode"), hash);
					} else {
						alert('We got no result!');
					}
				},
				error: function(resp) {
					console.log(resp);
				}
			});
		});

	</script>
</body></html>