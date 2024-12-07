CREATE INDEX tx_b_imopenlines_session_index_search_content ON b_imopenlines_session_index USING GIN (to_tsvector('english', search_content));
CREATE INDEX tx_b_imopenlines_chat_index_search_title ON b_imopenlines_chat_index USING GIN (to_tsvector('english', search_title));
