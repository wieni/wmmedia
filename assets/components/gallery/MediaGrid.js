import React from 'react';
import PropTypes from 'prop-types';
import InfiniteScroll from 'react-infinite-scroller';
import queryString from 'query-string';
import { parse } from 'date-fns';

import MediaItem from './MediaItem';
import MediaPreview from './MediaPreview';
import { MediaItemList as MediaItemListPropType } from './propTypes';

class MediaGrid extends React.Component {
    constructor(props) {
        super(props);

        this.state = {
            isPreviewVisible: false,
            itemInPreview: 0,
            items: props.items,
            hasMore: props.total > props.items.length,
            page: 0,
        };
    }

    setItemInPreview = (itemInPreview) => {
        this.setState({ itemInPreview });
    };

    setPreviewVisible = (isPreviewVisible) => {
        this.setState({ isPreviewVisible });
    };

    loadItems = () => {
        let { page } = this.state;

        page++;

        fetch(`/admin/api/media/paginate?${queryString.stringify({ page })}`, { credentials: 'include' })
            .then((response) => {
                if (response.status >= 400) {
                    throw new Error('Bad response from server');
                }
                return response.json();
            })
            .then(({ items, total }) => {
                this.setState({
                    hasMore: this.state.items.length + items.length < total,
                    page,
                    items: [
                        ...this.state.items,
                        ...items.map(item => ({
                            ...item,
                            dateCreated: parse(item.dateCreated * 1000),
                            dateChanged: parse(item.dateChanged * 1000),
                        })),
                    ],
                });
            });
    };

    render() {
        const { items, itemInPreview, isPreviewVisible } = this.state;

        return [
            (
                <InfiniteScroll
                    pageStart={0}
                    threshold={350}
                    loadMore={this.loadItems}
                    hasMore={this.state.hasMore}
                    loader={<div className="media-loading" key={0}><div className="media-loading__spinner" /></div>}
                    initialLoad={false}
                >
                    <ul className="media-item-list">
                        {items.map((item, index) => (
                            <MediaItem
                                data={item}
                                index={index}
                                key={item.id}
                                setItemInPreview={this.setItemInPreview}
                                setPreviewVisible={this.setPreviewVisible}
                            />
                        ))}
                    </ul>
                </InfiniteScroll>
            ),
            (
                <MediaPreview
                    item={items[itemInPreview]}
                    index={itemInPreview}
                    total={items.length}
                    isVisible={isPreviewVisible}
                    setVisible={this.setPreviewVisible}
                    setItemInPreview={this.setItemInPreview}
                />
            ),
        ];
    }
}

MediaGrid.propTypes = {
    // eslint-disable-next-line react/no-typos
    items: MediaItemListPropType.isRequired,
    total: PropTypes.number.isRequired,
};

export default MediaGrid;
