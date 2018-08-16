<div class="wrap">
<h1>
<?php
    echo __('Chat History','jms-live-chat');
?> <a href="
<?php
global $wp;
echo $wp->request;
?>admin.php?page=jms-live-chat-sub1" class="page-title-action">
<?php
    echo __('Back','jms-live-chat');
?>
</a>
</h1>


<form id="posts-filter" method="get">

<!--
<p class="search-box">
	<label class="screen-reader-text" for="post-search-input">搜索文章:</label>
	<input type="search" id="post-search-input" name="s" value="">
	<input type="submit" id="search-submit" class="button" value="搜索文章">
</p>
-->

<input type="hidden" name="post_status" class="post_status_page" value="all">
<input type="hidden" name="post_type" class="post_type_page" value="post">
<?php wp_nonce_field( 'new_live_chat' ); ?>

<!--
<div class="tablenav top">
    <div class="alignleft actions bulkactions">
        <label for="bulk-action-selector-top" class="screen-reader-text">选择批量操作</label>
        <select name="action" id="bulk-action-selector-top">
        <option value="-1">批量操作</option>
            <option value="edit" class="hide-if-no-js">编辑</option>
            <option value="trash">移至回收站</option>
        </select>
        <input type="submit" id="doaction" class="button action" value="应用">
    </div>

    <div class="alignleft actions">
        <label for="filter-by-date" class="screen-reader-text">按日期筛选</label>
        <select name="m" id="filter-by-date">
            <option selected="selected" value="0">全部日期</option>
            <option value="201611">2016年十一月</option>
        </select>
        <label class="screen-reader-text" for="cat">按分类过滤</label>
        <select name="cat" id="cat" class="postform">
            <option value="0">所有分类目录</option>
            <option class="level-0" value="1">未分类</option>
        </select>
        <input type="submit" name="filter_action" id="post-query-submit" class="button" value="筛选">
    </div>

    <div class="tablenav-pages one-page">
        <span class="displaying-num">3项目</span>
        <span class="pagination-links">
            <span class="tablenav-pages-navspan" aria-hidden="true">«</span>
            <span class="tablenav-pages-navspan" aria-hidden="true">‹</span>
            <span class="paging-input">第<label for="current-page-selector" class="screen-reader-text">当前页</label><input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="1" aria-describedby="table-paging"><span class="tablenav-paging-text">页，共<span class="total-pages">1</span>页</span></span>
            <span class="tablenav-pages-navspan" aria-hidden="true">›</span>
            <span class="tablenav-pages-navspan" aria-hidden="true">»</span>
        </span>
    </div>
    <br class="clear">
</div>
-->

<h2 class="screen-reader-text"><?php echo __('Message List','jms-live-chat'); ?></h2>

<table class="wp-list-table widefat fixed striped posts">
	<thead>
	<tr>
		<td id="cb" class="manage-column column-cb check-column">
            <label class="screen-reader-text" for="cb-select-all-1">全选</label>
            <!--<input id="cb-select-all-1" type="checkbox">-->
        </td>
        <th scope="col" id="chatid" class="manage-column column-author">
            <?php //echo __('ID','jms-live-chat');?>
        </th>
        <th scope="col" id="author" class="manage-column column-author">
            <?php echo __('Customer Name','jms-live-chat');?>
        </th>
        <th scope="col" id="categories" class="manage-column column-categories">
            <?php echo __('Date (UTC)','jms-live-chat');?>
        </th>
        <th scope="col" id="categories" class="manage-column column-title">
            <?php echo __('Message','jms-live-chat');?>
        </th>
        <th scope="col" id="categories" class="manage-column column-categories">
            <?php echo __('Status','jms-live-chat');?>
        </th>
        <th scope="col" id="categories" class="manage-column column-categories">
            <?php echo __('Last Replied By','jms-live-chat');?>
        </th>
    </tr>
	</thead>

	<tbody id="the-list">
    <?php
    if(isset($result)) {
        $arrayIndex = 1;
        foreach($result as $data) {

    ?>
		<tr id="post-20" class="iedit author-self level-0 post-20 type-post status-publish format-standard hentry category-uncategorized">
			<th scope="row" class="check-column">
                <label class="screen-reader-text" for="cb-select-20">选择文章2</label>
                <!--<input id="cb-select-20" type="checkbox" name="post[]" value="20">-->
                <div class="locked-indicator"></div>
            </th>

            <td class="chatid column-title">
                <strong><?php echo $arrayIndex++; ?></strong>
            </td>
    
            <td class="author column-author">
                <?php
                    echo $data["user_name"];
                ?></a></strong>
            </td>
    
            <td class="author column-author">
                <?php
                    echo $data["post_time"];
                ?>
            </td>

            <td class="column-title">
                <?php
                    echo $data["message"];
                ?>
            </td>

            <td class="author column-author">
                <?php
                    if($data["status"] == 0) {
                        echo __('Not Replied','jms-live-chat');
                    } else if($data["status"] == 1) {
                        echo __('Replied','jms-live-chat');
                    } else if($data["status"] == 2) {
                        echo __('Finished','jms-live-chat');
                    }
                ?>
            </td>
            
            <td class="categories column-categories">
                <?php
                    if(!empty($data["reply_by"])) {
                        $agent = new WP_User($data["reply_by"]);
                        echo $agent->user_nicename;
                    }
                ?>
            </td>
        </tr>
    <?php
        }
    }
    ?>
	</tbody>

	<tfoot>
   	</tfoot>
</table>

<!--
	<div class="tablenav bottom">
        <div class="alignleft actions bulkactions">
			<label for="bulk-action-selector-bottom" class="screen-reader-text">选择批量操作</label>
            <select name="action2" id="bulk-action-selector-bottom">
                <option value="-1">批量操作</option>
                <option value="edit" class="hide-if-no-js">编辑</option>
                <option value="trash">移至回收站</option>
            </select>
            <input type="submit" id="doaction2" class="button action" value="应用">
		</div>

        <div class="tablenav-pages one-page">
            <span class="displaying-num">3项目</span>
            <span class="pagination-links">
                <span class="tablenav-pages-navspan" aria-hidden="true">«</span>
                <span class="tablenav-pages-navspan" aria-hidden="true">‹</span>
                <span class="screen-reader-text">当前页</span>
                <span id="table-paging" class="paging-input">
                    <span class="tablenav-paging-text">第1页，共<span class="total-pages">1</span>页</span>
                </span>
                <span class="tablenav-pages-navspan" aria-hidden="true">›</span>
                <span class="tablenav-pages-navspan" aria-hidden="true">»</span>
            </span>
        </div>
        <br class="clear">
	</div>
-->
</form>
<br class="clear">
</div>