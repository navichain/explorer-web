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
						<h2 class="section-title">Latest Blocks</h2>
					</div>
				</div>
				<div class="offset-lg-3 col-lg-6">
					<div class="center-text">
						<p>This site refreshs each 30s</p>
					</div>
				</div>
			</div>			
			<div class="row">
				<div class="col-lg-12">
					<div class="table-responsive">
						<table class="table table-striped table-latests">
							<thead>
								<tr>
									<th>Hash</th>
									<th>Block</th>
									<th>Date/Time</th>
									<th>Transactions</th>
									<th>Difficulty</th>
								</tr>
							</thead>
							<tbody id="blocks-list">
							</tbody>
						</table>
					</div>
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
	<script type="text/javascript">
		var base_url = "http://207.148.77.154:3303";

		function formatDate(date) {
			var d = new Date(date * 1000);
			return [d.getFullYear(), d.getMonth() + 1, d.getDate()].join('-') + " " + [d.getHours(), d.getMinutes(), d.getSeconds()].join(':');
		}

		function getLatestBlocks() {
			console.log('resfresh');
			$.ajax({
				url: base_url + "/latest-blocks/20",
				type: "get",
				dataType: "json",
				success: function(resp) {
					resp.reverse();
					var latestBlockHeight = resp[0].height;
					$('#latest-block-number').html(latestBlockHeight);

					$('#blocks-list').html('');
					$.each(resp, function(index, blockInfo) {
						var hash = "<td>" + blockInfo.hash.substr(0,16) + "</td>";
						var block = "<td> #" + blockInfo.height + "</td>";
						var time = "<td>" + formatDate(blockInfo.time) + "</td>";
						var transaction = "<td>" + blockInfo.tx.length + "</td>";
						var link = "onclick='window.location.href=\"block.php?hash="+ blockInfo.hash +"\"'";
						var type = "<td>"+ blockInfo.difficulty +"</td>";

						var newTr = "<tr "+ link +" class='cursor-pointer'>" + hash + block + time + transaction + type + "</tr>";
						$('#blocks-list').append(newTr);
					});
				}
			});
			setTimeout(getLatestBlocks, 30000);
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
			getLatestBlocks();
		});

	</script>
</body></html>