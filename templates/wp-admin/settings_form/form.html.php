<div class="wrap">
	<div id="{icon}" class="icon32">
		<br />
	</div>

	<h2><?php echo $title; ?></h2>

	<form method="post" action="options.php">
		<?php echo $hidden_fields; ?>

		<table class="form-table">
			<?php echo $input_fields; ?>

			<tr valign="top">
				<th scope="row">
					<label>&nbsp;</label>
				</th>
				<td>
					<input type="submit" class="button-primary" value="<?php echo $submit; ?>" />
				</td>
			</tr>
		</table>
	</form>
</div>