CREATE INDEX tx_b_tasks_search_index_search_index ON b_tasks_search_index USING GIN (to_tsvector('english', search_index));
CREATE INDEX tx_b_tasks_flow_search_index_search_index ON b_tasks_flow_search_index USING GIN (to_tsvector('english', search_index));
