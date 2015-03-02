<tr valign="top">
	<th scope="row">
		<label for="<?php echo $key; ?>"><?php echo $name; ?></label>
	</th>
	<td>
		<input type="<?php echo isset($type) ? $type : "text"; ?>" name="<?php echo $key; ?>" id="<?php echo $key; ?>" value="<?php echo isset($value) ? $value : ""; ?>" class="<?php echo isset($class) ? $class : "regular-text"; ?>" <?php echo isset($checked) ? $checked : ""; ?> />

		<?php if (isset($description)): ?>
			<p class="description"><?php echo $description; ?></p>
		<?php endif; ?>
	</td>
</tr>