<?php

class AW_Advancedreports_Additional_SalesbycategoryController extends AW_Advancedreports_Controller_Action
{
    protected function _getCategoryCollection()
    {
        return Mage::getResourceModel('catalog/category_collection');
    }

    public function getCategoryAction()
    {
        if ($category = $this->getRequest()->getParam('category')) {
            Mage::register(AW_Advancedreports_Helper_Setup::DATA_KEY_REPORT_ID, 'gridCategory');
            $category = base64_decode($category);
            $category = $this->escapeHtml($category);
            $category = str_replace("&amp;", "&", $category);
            $collection = $this->_getCategoryCollection();
            $collection->addNameToResult();
            $collection->addFieldToFilter('name', array('like' => "%{$category}%"));

            $categories = array();
            foreach ($collection as $item) {
                $categories[] = $item->getName();
            }

            $this->_ajaxResponse(
                array(
                    'count' => count($categories),
                    'category'   => $category,
                    'categories'  => $categories
                )
            );
            return;

        }
        $this->_ajaxResponse(array('count' => 0));
    }

    /**
     * Escape html entities
     *
     * @param   mixed $data
     * @param   array $allowedTags
     *
     * @return  mixed
     */
    public function escapeHtml($data, $allowedTags = null)
    {
        if (is_array($data)) {
            $result = array();
            foreach ($data as $item) {
                $result[] = $this->escapeHtml($item);
            }
        } else {
            // process single item
            if (strlen($data)) {
                if (is_array($allowedTags) and !empty($allowedTags)) {
                    $allowed = implode('|', $allowedTags);
                    $result = preg_replace('/<([\/\s\r\n]*)(' . $allowed . ')([\/\s\r\n]*)>/si', '##$1$2$3##', $data);
                    $result = htmlspecialchars($result);
                    $result = preg_replace('/##([\/\s\r\n]*)(' . $allowed . ')([\/\s\r\n]*)##/si', '<$1$2$3>', $result);
                } else {
                    $result = htmlspecialchars($data);
                }
            } else {
                $result = $data;
            }
        }
        return $result;
    }
}