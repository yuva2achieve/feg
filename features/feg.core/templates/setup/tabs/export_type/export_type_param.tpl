<select name="export_type_params_add">
	<option value="">{$translate->_('feg.export_type.peek.add_param')|capitalize}</option>
	{foreach from=$export_type_params item=export_type_param key=export_type_param_id}
		{if $type == $export_type_param->recipient_type}
			<option id="{$export_type_param->id}"{if isset($export_type_param->options['default'])} value="{$export_type_param->options['default']}"{/if}>{$export_type_param->name}</option>
		{/if}
	{/foreach}
</select>
