<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div> 

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="60%">
			<table cellpadding="0" cellspacing="0" border="0">
				<tr>
					<td valign="top" width="33%">
						Mail Statics:<br>
						<div id="postfix_mailq_stats"></div>
						<div id="postfix_sent_stats"></div><br>
					</td>
					<td valign="top" width="33%">
						Fax Statics:<br>
						<div id="showfaxquestats"></div>
						<div id="showfaxstats"></div>
					</td>
					<td valign="top" width="33%">
						SNPP (Paging) Statics:<br>
						<div id="snpp_stats"></div><br>
					</td>
				</tr>
				<tr>
					<td valign="top" width="33%">
						Hardware Statics:<br>
						<div id="hardware_stats"></div><br>
					</td>
					<td valign="top" width="33%">
						TBD:<br>
						<div id="tbd1_stats"></div><br>
					</td>
					<td valign="top" width="33%">
						TBD:<br>
						<div id="tbd2_stats"></div><br>
					</td>
				</tr>
			</table>
		</td>
		<td valign="top" width="40%" align="right">
			{if !empty($views)}
				{foreach from=$views item=view name=views}
					<div id="view{$view->id}">
						{$view->render()}
					</div>
				{/foreach}
			{/if}
		</td>
	</tr>
</table>


<br>

{include file="file:$core_tpl/whos_online.tpl"}

<script>
$(document).ready(function() {
	$("#postfix_mailq_stats").load("{devblocks_url}ajax.php?c=stats&a=showPostfixMailqStats{/devblocks_url}");
	var refreshId = setInterval(function() {
		$("#postfix_mailq_stats").load("{devblocks_url}ajax.php?c=stats&a=showPostfixMailqStats{/devblocks_url}");
	}, 5000);
});
$(document).ready(function() {
	$("#postfix_sent_stats").load("{devblocks_url}ajax.php?c=stats&a=showPostfixStats{/devblocks_url}");
	var refreshId = setInterval(function() {
		$("#postfix_sent_stats").load("{devblocks_url}ajax.php?c=stats&a=showPostfixStats{/devblocks_url}");
	}, 60000);
});

$(document).ready(function() {
	$("#showfaxquestats").load("{devblocks_url}ajax.php?c=stats&a=showFaxQueStats{/devblocks_url}");
	var refreshId = setInterval(function() {
		$("#showfaxquestats").load("{devblocks_url}ajax.php?c=stats&a=showFaxQueStats{/devblocks_url}");
	}, 5000);
});
$(document).ready(function() {
	$("#showfaxstats").load("{devblocks_url}ajax.php?c=stats&a=showFaxStats{/devblocks_url}");
	var refreshId = setInterval(function() {
		$("#showfaxstats").load("{devblocks_url}ajax.php?c=stats&a=showFaxStats{/devblocks_url}");
	}, 60000);
});

</script>