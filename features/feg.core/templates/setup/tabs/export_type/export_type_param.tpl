<select name="export_type_params_add">
	{foreach from=$export_type_params item=export_type_param key=export_type_param_id}
		{if $export_type->recipient_type == $export_type_param->recipient_type}
			<option value="{$export_type_param->id}">{$export_type_param->name}</option>
		{/if}
	{/foreach}
</select>
