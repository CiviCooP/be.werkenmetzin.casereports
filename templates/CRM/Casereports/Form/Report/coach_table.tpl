{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{if (!$chartEnabled || !$chartSupported )&& $rows}
	{foreach from=$rows item=corerows key=corerowid}
		<h4>{ $corerows[0].client } - { $corerows[0].chequenummer } - { $corerows[0].case_start_date }</h4>
		<table class="report-layout display">
			<thead class="sticky">
				<tr>
					<th class="reports-header" width="25%">
						<div class="sticky-header" style="position: fixed; display: none; top: 23px; margin-left: -10px; margin-right: -18px; padding: 3px 86px 3px 10px;"><span>Type Activiteit</span></div>
						Type Activiteit
					</th>
					<th class="reports-header" width="25%">
						<div class="sticky-header" style="position: fixed; display: none; top: 23px; margin-left: -10px; margin-right: -18px; padding: 3px 62px 3px 10px;"><span>Datum Activiteit</span></div>
						Datum Activiteit
					</th>
					<th class="reports-header" width="25%">
						<div class="sticky-header" style="position: fixed; display: none; top: 23px; margin-left: -10px; margin-right: -18px; padding: 3px 59px 3px 10px;"><span>Duur Activiteit</span></div>
						Duur Activiteit
					</th>
					<th class="reports-header" width="25%">
						<div class="sticky-header" style="position: fixed; display: none; top: 23px; margin-left: -10px; margin-right: -18px; padding: 3px 63px 3px 10px;"><span>Status Activiteit</span></div>
						Status Activiteit
					</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$corerows item=activityrow key=activityrowid}
					<tr class="{cycle values="odd-row,even-row"} crm-report" id="crm-report_0">
						<td class="crm-report-activity_type">
							{ $activityrow.activity_type }
						</td>
						<td class="crm-report-activity_date">
							{ $activityrow.activity_date }
						</td>
						<td class="crm-report-activity_duration">
							{ $activityrow.activity_duration }
						</td>
						<td class="crm-report-activity_status">
							{ $activityrow.activity_status }
						</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	{/foreach}
{/if}
