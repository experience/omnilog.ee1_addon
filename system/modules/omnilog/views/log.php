<div class="experienceinternet">
<table class="tableBorder" cellpadding="0" cellspacing="0" width="100%">
    <thead>
        <tr style="text-align : left;">
            <th class="tableHeading">&nbsp;</th>
            <th class="tableHeading"><?php echo lang('thd_date'); ?></th>
            <th class="tableHeading"><?php echo lang('thd_addon'); ?></th>
            <th class="tableHeading"><?php echo lang('thd_type'); ?></th>
            <th class="tableHeading"><?php echo lang('thd_notify_admin'); ?></th>
            <th class="tableHeading"><?php echo lang('thd_message'); ?></th>
            <th class="tableHeading" style="width : 30%"><?php echo lang('thd_extended_data'); ?></th>
        </tr>
    </thead>

    <tbody>
    <?php
      $count = 0;
      foreach ($log_entries AS $log_entry):
        $row_class = $count++ % 2 ? 'tableCellOne' : 'tableCellTwo';
    ?>
        <tr>
            <td class="<?php echo $row_class; ?>">
              <?php echo $log_entry->get_log_entry_id(); ?></td>
            <td class="<?php echo $row_class; ?>">
              <span style="white-space : nowrap;"><?php echo date('j M, Y', $log_entry->get_date()); ?></span>
              <span style="white-space : nowrap;">at <?php echo date('g:ia', $log_entry->get_date()); ?></span>
            </td>
            <td class="<?php echo $row_class; ?>"><?php echo $log_entry->get_addon_name(); ?></td>
            <td class="<?php echo $row_class; ?>"><?php echo lang('lbl_type_' .$log_entry->get_type()); ?></td>
            <td class="<?php echo $row_class; ?>"><?php
              if ($log_entry->get_notify_admin() !== TRUE):
                echo lang('lbl_no');
              else:
                if ($admin_emails = $log_entry->get_admin_emails()):
                  foreach ($admin_emails AS $email):
                    echo $email .'<br />';
                  endforeach;
                else:
                  echo $webmaster_email .'<br />';
                endif;
              endif;
            ?></td>
            <td class="<?php echo $row_class; ?>"><?php echo nl2br($log_entry->get_message()); ?></td>
            <?php $extended_data = nl2br($log_entry->get_extended_data());
              if ( ! $extended_data): ?>
            <td class="<?php echo $row_class; ?>">&nbsp;</td>
            <?php else: ?>
            <td class="extended_data <?php echo $row_class; ?>"><?php echo $extended_data; ?></td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>

var EE = {omnilog: {lang: {}}};
<?php foreach ($js_lang AS $key => $val): ?>
EE.omnilog.lang.<?php echo $key; ?> = '<?php echo $val; ?>';
<?php endforeach; ?>

</script>
</div><!-- /.experienceinternet -->
