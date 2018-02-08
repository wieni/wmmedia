import React from 'react';

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
        };
    }

    setItemInPreview = (itemInPreview) => {
        this.setState({ itemInPreview });
    };

    setPreviewVisible = (isPreviewVisible) => {
        this.setState({ isPreviewVisible });
    };

    render() {
        const { items, itemInPreview, isPreviewVisible } = this.state;

        return [
            (
                <ul className="media-item-list">
                    {items.map((item, index) => (
                        <MediaItem
                            data={item}
                            index={index}
                            setItemInPreview={this.setItemInPreview}
                            setPreviewVisible={this.setPreviewVisible}
                        />
                    ))}
                </ul>
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
};

export default MediaGrid;
