<?php

namespace StgHelpdesk\Ticket;

use StgHelpdesk\Helpers\Stg_Helper_UploadFiles;

class Stg_Helpdesk_Filter
{
    /**
     * Filtering the data to be sent to the database
     *
     * @param $data
     * @param $postarr
     * @return mixed
     */
    public static function filterData($data, $postarr)
    {

        if (!isset($data['post_type']) || STG_HELPDESK_POST_TYPE !== $data['post_type']) {
            return $data;
        }

        /**
         * If the ticket is being trashed we don't do anything.
         */
        if ('trash' === $data['post_status']) {
            return $data;
        }

        /**
         * Do not affect auto drafts
         */
        if ('auto-draft' === $data['post_status']) {
            return $data;
        }

        if (isset($postarr["auto_draft"]) && $postarr["auto_draft"] === "1") {
            $data['post_content'] = $postarr["stgh_comment"];

            if(isset($postarr['mm']) && isset($postarr['jj']) && isset($postarr['hh']) && isset($postarr['hh']) && isset($postarr['mn']) && isset($postarr['ss']))
            {
                $data['post_date'] = $postarr['aa']."-".$postarr['mm']."-".$postarr['jj']." ".$postarr['hh'].":".$postarr['mn'].":".$postarr['ss'];
                $data['post_date_gmt'] = get_gmt_from_date( $data['post_date'], $format = 'Y-m-d H:i:s');
            }

        }

        // change status
        if (isset($_POST['post_status_override']) && !empty($_POST['post_status_override'])) {
            $statuses = stgh_get_statuses();
            if (array_key_exists($_POST['post_status_override'], $statuses)) {
                $data['post_status'] = $_POST['post_status_override'];
            } else {
                $data['post_status'] = 'new';
            }
        }

        return $data;
    }
}