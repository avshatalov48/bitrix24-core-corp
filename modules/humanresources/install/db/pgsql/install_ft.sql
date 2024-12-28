CREATE INDEX tx_b_hr_structure_node_name ON b_hr_structure_node USING GIN (to_tsvector('english', name));
CREATE INDEX tx_b_hr_hcmlink_person_index_search_content ON b_hr_hcmlink_person_index USING GIN (to_tsvector('english', search_content));
