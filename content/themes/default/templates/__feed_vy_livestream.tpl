
{assign var=broadcast_data22 value=$live_stream->getBroadcastData($_post['post_id'])}

 
{if $broadcast_data22['islivenow'] == 'yes'}
		 <div class="vy_lv_feedb1">
				<a href="javascript:void(0);" class="vy-lv-focus-play" onclick="vy_global_openLiveStream(event,this,{$_post['post_id']});">
					<div class="vy_lv_feedlvbgcover"></div> 
					<img src="{$system['system_url']}/{$broadcast_data22['full_cover_path']}"/>
					<div class="vy_lv_playinsic"></div>
					{if $broadcast_data22['islivenow'] == 'yes'}
					<div class="vy-lv-livenow-ic live_now">{$live_stream->svg['live']}</div>
					{/if}


				</a>
		  </div>
{else}
 <!--

        <div class="overflow-hidden">
          <video class="js_videojs video-js vjs-fluid vjs-default-skin" id="video-{$_post['live']['live_id']}{if $pinned || $boosted}-{$_post['post_id']}{/if}" {if $_post['live']['video_thumbnail']}poster="{str_replace("/content/uploads","",$system['system_uploads'])}/{$broadcast_data22['full_cover_path']}" {/if} controls preload="auto">
            <source src="{str_replace("/content/uploads","",$system['system_uploads'])}/{$broadcast_data22['full_file_path']}" type="application/x-mpegURL">
          </video>
        </div>-->
  {if $_post['is_hidden']}      
  <div class="vylv_sngine_live_processing">The broadcast is ended, and will be available for watch soon.</div>
  {else}
 <div class="overflow-hidden">
        <video style="width:100%;height:auto;" autoplay muted controls src="{str_replace("/content/uploads","",$system['system_uploads'])}/{$broadcast_data22['full_file_path']}" preload="metadata" poster="{str_replace("/content/uploads","",$system['system_uploads'])}/{$broadcast_data22['full_cover_path']}"></video>
 </div>
 {/if}
{/if}