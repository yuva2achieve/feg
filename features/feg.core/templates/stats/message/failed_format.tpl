<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formAccountFailurePeek" name="formAccountFailurePeek">
<input type="hidden" name="c" value="account">
<input type="hidden" name="a" value="createNewCustomer">
<input type="hidden" name="message_id" value="{$message->id}">
<input type="hidden" name="account_id" id="account_id" value="{$message->account_id}">
{if isset($message->params['account_name'])}
	<input type="hidden" name="account_name" value="{$message->params['account_name']}">
{/if}

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	{if $message->account_id == 0}
	<tr>
		<td>
			{if isset($message->params['account_name'])}
				{if $active_worker->hasPriv('core.access.customer.create')}
					<a href="javascript:;" onclick="$('#formAccountFailurePeek').trigger('submit');">
					<b>{$message->params['account_name']}</b></a>&nbsp;
				{else}{$message->params['account_name']}&nbsp;
				{/if}
			{else}{$translate->_('feg.message_recipient.status_unknown')|capitalize}
			{/if}
		</td>
	</tr>
	{if $active_worker->hasPriv('core.access.message.assign')}
	<tr>
		<td nowrap="nowrap" align="right">{$translate->_('feg.message.assign_account.search')|capitalize}</td>
		<td>
			<input type="text" name="customer_account_search" id="customer_account_search" value="" style="width:98%;">
		</td>
	</tr>
	<tr>
		<td nowrap="nowrap" align="right">{$translate->_('feg.message.assign_account.name')|capitalize}</td>
		<td>
			<div id="assign_to_account_results_name">&nbsp;</div>
		</td>
	</tr>
	<tr>
		<td nowrap="nowrap" align="right">
				{$translate->_('feg.message.assign_account')}&nbsp;
		</td>
		<td>
				<a id="customer_account_assign_link" href="javascript:;">
				<b><span id="assign_to_account_results_number">&nbsp;</span></b></a>&nbsp;
		</td>
	</tr>
	{/if}
	{else}
	<tr>
		<td nowrap="nowrap" align="right">
				{$translate->_('feg.message.assigned_to_account')}&nbsp;
		</td>
		<td>
				<b>{$message->account_id}</b>&nbsp;
		</td>
	</tr>
	{/if}	
	<tr>
		<td nowrap="nowrap" align="right">{$translate->_('feg.message.id')|capitalize} </td>
		<td>{$id}</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">
			{$translate->_('feg.message.message')|capitalize}:
		</td>
		<td width="100%">
			{foreach from=$message_lines item=line name=line_id}
				{$line}<br>
			{/foreach}
		</td>
	</tr>
</table>
<br>

<button type="button" onclick="genericPanel.dialog('close');"><span class="feg-sprite sprite-delete_gray"></span>{$translate->_('common.cancel')|capitalize}</button>
</form>

<script type="text/javascript" language="JavaScript1.2">
	$(genericPanel).one('dialogopen',function(event,ui) {
		genericPanel.dialog('option','title','Failed Format Messages'); 
	});
	$(document).ready(function() {
		$("#customer_account_search").autocomplete({
			source: "{devblocks_url}ajax.php?c=account&a=searchCustomerJson{/devblocks_url}",
			minLength: 1,
			select: function( event, ui ) {
				var account = ui.item ? ui.item.value : this.value;
				$.getJSON("{devblocks_url}ajax.php?c=account&a=showCustomerJson&search="+account+"{/devblocks_url}", function(data) {
					$('#assign_to_account_results_name').text(data.account_name);
					$('#assign_to_account_results_number').text(data.account_number);
					$('#account_id').val(data.account_number);
					$("#customer_account_assign_link").click(function() {
						var an = $('#account_id').val();
						$.getJSON("{devblocks_url}ajax.php?c=account&a=setCustomerAccountNumber&m_id={$id}&acc_num="+an+"{/devblocks_url}", function(data) {
							genericPanel.dialog('close');
						});
					});
				});
			}
		});
	});
</script>
