<?php

class Xfof_OnlineStatus_Model_AspPost extends XFCP_Xfof_OnlineStatus_Model_AspPost
{
    public function preparePostJoinOptions(array $fetchOptions)
    {
        $array = parent::preparePostJoinOptions($fetchOptions);

        if(!empty($fetchOptions['join']))
        {
            if($fetchOptions['join'] & self::FETCH_USER)
            {
                $array['selectFields'] .= ', session.view_date';
                $array['joinTables'] .= 'LEFT OUTER JOIN xf_session_activity AS session ON post.user_id = session.user_id';
            }
        }

        return $array;
    }
}