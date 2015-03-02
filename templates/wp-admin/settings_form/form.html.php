<div class="wrap">
	<div class="icon32 <?php echo isset($icon) ? $icon : ""; ?>">
		<br />
	</div>

	<h2><?php echo isset($title) ? $title : ""; ?></h2>

	<form method="post" action="options.php">
		<?php echo isset($hidden_fields) ? $hidden_fields : ""; ?>

		<table class="form-table">
			<?php echo isset($input_fields) ? $input_fields : ""; ?>

			<tr valign="top">
				<th scope="row">
					<label>&nbsp;</label>
				</th>
				<td>
					<input type="submit" class="button-primary" value="<?php echo isset($submit) ? $submit : ""; ?>" />
				</td>
			</tr>
		</table>
	</form>
</div>