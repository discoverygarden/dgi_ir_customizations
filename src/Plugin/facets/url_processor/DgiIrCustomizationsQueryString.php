<?php

namespace Drupal\dgi_ir_customizations\Plugin\facets\url_processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Event\QueryStringCreated;
use Drupal\facets\Plugin\facets\url_processor\QueryString;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;

/**
 * Extension of QueryString to ensure some bits.
 *
 * @FacetsUrlProcessor(
 *   id = "dgi_ir_customizations_query_string",
 *   label = @Translation("DGI IR Customizations Query String"),
 *   description = @Translation("Uses GET parameters, but ensures construction according to how we use facets.")
 * )
 */
class DgiIrCustomizationsQueryString extends QueryString {

  /**
   * {@inheritdoc}
   */
  public function buildUrls(FacetInterface $facet, array $results) {
    // Largely a copypaste of parent::buildUrls(), but make sure to include
    // the search_api_fulltext query param.
    // No results are found for this facet, so don't try to create urls.
    if (empty($results)) {
      return [];
    }

    // First get the current list of get parameters.
    $get_params = $this->request->query;

    // When adding/removing a filter the number of pages may have changed,
    // possibly resulting in an invalid page parameter.
    if ($get_params->has('page')) {
      $current_page = $get_params->get('page');
      $get_params->remove('page');
    }

    // Set the url alias from the facet object.
    $this->urlAlias = $facet->getUrlAlias();

    $facet_source_path = $facet->getFacetSource()->getPath();
    $request = $this->getRequestByFacetSourcePath($facet_source_path);
    $requestUrl = $this->getUrlForRequest($facet_source_path, $request);

    $original_filter_params = [];
    foreach ($this->getActiveFilters() as $facet_id => $values) {
      $values = array_filter($values, static function ($it) {
        return $it !== NULL;
      });
      foreach ($values as $value) {
        $original_filter_params[] = $this->getUrlAliasByFacetId($facet_id, $facet->getFacetSourceId()) . ":" . $value;
      }
    }

    /** @var \Drupal\facets\Result\ResultInterface[] $results */
    foreach ($results as &$result) {
      // Reset the URL for each result.
      $url = clone $requestUrl;

      // Sets the url for children.
      if ($children = $result->getChildren()) {
        $this->buildUrls($facet, $children);
      }

      if ($result->getRawValue() === NULL) {
        $filter_string = NULL;
      }
      else {
        $filter_string = $this->urlAlias . $this->getSeparator() . $result->getRawValue();
      }
      $result_get_params = clone $get_params;

      $filter_params = $original_filter_params;

      // If the value is active, remove the filter string from the parameters.
      if ($result->isActive()) {
        foreach ($filter_params as $key => $filter_param) {
          if ($filter_param == $filter_string) {
            unset($filter_params[$key]);
          }
        }
        if ($facet->getEnableParentWhenChildGetsDisabled() && $facet->getUseHierarchy()) {
          // Enable parent id again if exists.
          $parent_ids = $facet->getHierarchyInstance()->getParentIds($result->getRawValue());
          if (isset($parent_ids[0]) && $parent_ids[0]) {
            // Get the parents children.
            $child_ids = $facet->getHierarchyInstance()->getNestedChildIds($parent_ids[0]);

            // Check if there are active siblings.
            $active_sibling = FALSE;
            if ($child_ids) {
              foreach ($results as $result2) {
                if ($result2->isActive() && $result2->getRawValue() != $result->getRawValue() && in_array($result2->getRawValue(), $child_ids)) {
                  $active_sibling = TRUE;
                  continue;
                }
              }
            }
            if (!$active_sibling) {
              $filter_params[] = $this->urlAlias . $this->getSeparator() . $parent_ids[0];
            }
          }
        }

      }
      // If the value is not active, add the filter string.
      else {
        if ($filter_string !== NULL) {
          $filter_params[] = $filter_string;
        }

        if ($facet->getUseHierarchy()) {
          // If hierarchy is active, unset parent trail and every child when
          // building the enable-link to ensure those are not enabled anymore.
          $parent_ids = $facet->getHierarchyInstance()->getParentIds($result->getRawValue());
          $child_ids = $facet->getHierarchyInstance()->getNestedChildIds($result->getRawValue());
          $parents_and_child_ids = array_merge($parent_ids, $child_ids);
          foreach ($parents_and_child_ids as $id) {
            $filter_params = array_diff($filter_params, [$this->urlAlias . $this->getSeparator() . $id]);
          }
        }
        // Exclude currently active results from the filter params if we are in
        // the show_only_one_result mode.
        if ($facet->getShowOnlyOneResult()) {
          foreach ($results as $result2) {
            if ($result2->isActive()) {
              $active_filter_string = $this->urlAlias . $this->getSeparator() . $result2->getRawValue();
              foreach ($filter_params as $key2 => $filter_param2) {
                if ($filter_param2 == $active_filter_string) {
                  unset($filter_params[$key2]);
                }
              }
            }
          }
        }
      }

      // Allow other modules to alter the result url built.
      $this->eventDispatcher->dispatch(QueryStringCreated::NAME, new QueryStringCreated($result_get_params, $filter_params, $result, $this->activeFilters, $facet));

      asort($filter_params, \SORT_NATURAL);
      $result_get_params->set($this->filterKey, array_values($filter_params));

      if ($result_get_params->all() !== [$this->filterKey => []]) {
        $new_url_params = $result_get_params->all();

        // Facet links should be page-less.
        // See https://www.drupal.org/node/2898189.
        unset($new_url_params['page']);

        // Remove core wrapper format (e.g. render-as-ajax-response) paremeters.
        unset($new_url_params[MainContentViewSubscriber::WRAPPER_FORMAT]);

        // XXX: The 'search_api_fulltext' param is needed by our solr search
        // views to determine the base query to be run; if not present, no
        // results will be returned. However, if we ask the facet to build a URL
        // from outside the view, this param won't exist yet. Attaching here
        // manually to ensure the facet has at least a base search param.
        if (!isset($new_url_params['search_api_fulltext'])) {
          $new_url_params['search_api_fulltext'] = '';
        }


        // Set the new url parameters.
        $url->setOption('query', $new_url_params);
      }

      $result->setUrl($url);
    }

    // Restore page parameter again. See https://www.drupal.org/node/2726455.
    if (isset($current_page)) {
      $get_params->set('page', $current_page);
    }
    return $results;
  }

}
