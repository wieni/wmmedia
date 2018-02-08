import React from 'react';
import PropTypes from 'prop-types';
import { CopyToClipboard } from 'react-copy-to-clipboard';
import { MediaItem as MediaItemPropType } from './propTypes';

class MediaItem extends React.Component {
    constructor(props) {
        super(props);

        this.state = {
            isOverlayOpen: false,
            isUrlCopied: false,
        };
    }

    onOpenOverlay = () => {
        this.setState({ isOverlayOpen: true });
    };

    onCloseOverlay = () => {
        this.setState({ isOverlayOpen: false });
    };

    onOpenPreview = (index) => {
        this.props.setItemInPreview(index);
        this.props.setPreviewVisible(true);
        this.onCloseOverlay();
    };

    onCopy = () => {
        this.setState({ isUrlCopied: true });
        window.setTimeout(() => {
            this.setState({ isUrlCopied: false });
        }, 1000);
    };

    render() {
        const { data, index } = this.props;
        const { isOverlayOpen, isUrlCopied } = this.state;

        return (
            <li className="media-item" key={data.thumbUrl}>
                <div className={`media-item__image ${isOverlayOpen ? 'is-overlay-open' : ''}`}>
                    <button onClick={() => this.onOpenPreview(index)}>
                        <img src={data.thumbUrl} alt={data.label} />
                    </button>
                    <div className="media-item__gradient" />
                    <div className="media-item__info">
                        <p className="media-item__title">{data.label}</p>
                        <p className="media-item__subtitle">
                            {data.width && data.height && (
                                <span className="media-item__subtitle-item">{`${data.width} x ${data.height}`}</span>
                            )}
                            {data.size && (
                                <span className="media-item__subtitle-item">{data.size}</span>
                            )}
                        </p>
                    </div>
                    <svg className="media-item__more" viewBox="0 0 24 24" onClick={this.onOpenOverlay}>
                        <path fill="currentColor" d="M16,12A2,2 0 0,1 18,10A2,2 0 0,1 20,12A2,2 0 0,1 18,14A2,2 0 0,1 16,12M10,12A2,2 0 0,1 12,10A2,2 0 0,1 14,12A2,2 0 0,1 12,14A2,2 0 0,1 10,12M4,12A2,2 0 0,1 6,10A2,2 0 0,1 8,12A2,2 0 0,1 6,14A2,2 0 0,1 4,12Z" />
                    </svg>
                    <ul className={`media-item__overlay ${isOverlayOpen ? 'is-open' : ''}`}>
                        <li className="media-item__close media-icon media-icon--close">
                            <button className="media-icon media-icon--close" onClick={this.onCloseOverlay} />
                        </li>
                        <li className="media-action media-icon media-icon--preview">
                            <button onClick={() => this.onOpenPreview(index)}>Preview</button>
                        </li>
                        <li className="media-action media-icon media-icon--edit">
                            <a href={data.editUrl} target="_blank">Edit</a>
                        </li>
                        <li className="media-action media-icon media-icon--delete">
                            <a href={data.deleteUrl} target="_blank">Delete</a>
                        </li>
                        <li className="media-action media-icon media-icon--link">
                            <CopyToClipboard text={data.originalUrl} onCopy={this.onCopy}>
                                <button>{isUrlCopied ? 'Copied!' : 'Copy url'}</button>
                            </CopyToClipboard>
                        </li>
                        <li className="media-action media-icon media-icon--download">
                            <a href={data.originalUrl} download target="_blank">Download original</a>
                        </li>
                    </ul>
                </div>
            </li>
        );
    }
}

MediaItem.propTypes = {
    // eslint-disable-next-line react/no-typos
    data: MediaItemPropType.isRequired,
    index: PropTypes.number.isRequired,
    setItemInPreview: PropTypes.func.isRequired,
    setPreviewVisible: PropTypes.func.isRequired,
};

export default MediaItem;
