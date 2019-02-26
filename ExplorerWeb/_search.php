<!-- ***** Wellcome Area Start ***** -->
<section class="block-explorer-wrapper bg-bottom-center" id="welcome-1">
	<div class="block-explorer text">
		<div class="container text-center">
			<div class="row">
				<div class="col-lg-12 align-self-center">
					<h1>BLOCKCHAIN EXPLORER</h1>
				</div>
				<div class="offset-lg-3 col-lg-6">
					<p>Up To Block <span id="latest-block-number"></span></p>
				</div>
			</div>
		</div>
	</div>
	<div class="search">
		<div class="container">
			<div class="row">
				<div class="col-lg-12">
					<div class="input-wrapper">
						<div class="input">
							<input type="text" placeholder="block hash, transaction hash, or address" id="search-term" onkeyup="searchEnter(event)">
							<button onclick="search()"><i class="fa fa-search"></i></button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>	
<canvas class="particles-js-canvas-el" width="1903" height="360" style="width: 100%; height: 100%;"></canvas></section>
<!-- ***** Wellcome Area End ***** -->