import React from 'react';
import PropTypes from 'prop-types';
import { CopyToClipboard } from 'react-copy-to-clipboard';
import { format } from 'date-fns';

import { MediaItem as MediaItemPropType } from './propTypes';

class MediaPreview extends React.Component {
    constructor(props) {
        super(props);

        this.state = {
            defaultValue: '-',
            isUrlCopied: false,
        };
    }

    componentDidMount() {
        this.keyDownListener = window.addEventListener('keydown', this.onKeyDown);
    }

    componentWillUnmount() {
        window.removeEventListener('keydown', this.keyDownListener);
    }

    onCopy = () => {
        this.setState({ isUrlCopied: true });
        window.setTimeout(() => {
            this.setState({ isUrlCopied: false });
        }, 1000);
    };

    onClick = (e) => {
        const path = e.nativeEvent.propagationPath();
        const wrapper = document.querySelector('.media-preview__wrapper');

        if (path.includes(wrapper)) {
            return;
        }

        this.onClose();
    };

    onClose = () => {
        this.props.setVisible(false);
    };

    onKeyDown = (e) => {
        switch (e.keyCode) {
            case 27:
                this.onClose();
                break;
            case 39:
                if (this.hasNextItem()) {
                    this.onNextItem();
                }
                break;
            case 37:
                if (this.hasPreviousItem()) {
                    this.onPreviousItem();
                }
                break;
            default:
                break;
        }
    };

    onNextItem = () => {
        this.props.setItemInPreview(this.props.index + 1);
    };

    onPreviousItem = () => {
        this.props.setItemInPreview(this.props.index - 1);
    };

    hasNextItem = () => this.props.index < (this.props.total - 1);
    hasPreviousItem = () => this.props.index > 0;

    render() {
        const { isVisible, item } = this.props;
        const { defaultValue, isUrlCopied } = this.state;

        document.body.classList[isVisible ? 'add' : 'remove']('modal-open');

        return (
            <div className={`media-preview ${isVisible ? 'is-open' : ''}`} onClick={this.onClick}>
                <button className="media-preview__close media-icon media-icon--close" onClick={this.onClose} />
                <div className="media-preview__wrapper">
                    <div className="media-preview__image">
                        <img src={item.largeUrl} alt={item.label} />
                        <ul className="media-preview__action-container">
                            {item.operations.map(operation => (
                                <li className={`media-action media-icon media-icon--${operation.key}`}>
                                    <a href={operation.url}>{operation.title}</a>
                                </li>
                            ))}
                            <li className="media-action media-icon media-icon--link">
                                <CopyToClipboard text={item.originalUrl} onCopy={this.onCopy}>
                                    <button>{isUrlCopied ? 'Copied!' : 'Copy url'}</button>
                                </CopyToClipboard>
                            </li>
                            <li className="media-action media-icon media-icon--download">
                                <a href={item.originalUrl} download target="_blank">Download original</a>
                            </li>
                            <li
                                onClick={this.onPreviousItem}
                                style={{ visibility: this.hasPreviousItem() ? 'visible' : 'hidden' }}
                                className="media-action media-action--controls media-action--pull-right media-icon media-icon--previous"
                            />
                            <li
                                onClick={this.onNextItem}
                                style={{ visibility: this.hasNextItem() ? 'visible' : 'hidden' }}
                                className="media-action media-action--controls media-icon media-icon--next"
                            />
                        </ul>
                    </div>
                    <div className="media-preview__info">
                        <p className="media-preview__field">
                            <span className="media-preview__field-label">Name</span>
                            {item.label || defaultValue}
                        </p>
                        <p className="media-preview__field">
                            <span className="media-preview__field-label">Copyright</span>
                            <span dangerouslySetInnerHTML={{__html: item.copyright || defaultValue}} />
                        </p>
                        <p className="media-preview__field">
                            <span className="media-preview__field-label">Caption</span>
                            <span dangerouslySetInnerHTML={{__html: item.caption || defaultValue}} />
                        </p>
                        <p className="media-preview__field">
                            <span className="media-preview__field-label">Alternate</span>
                            {item.alternate || defaultValue}
                        </p>
                        <p className="media-preview__field">
                            <span className="media-preview__field-label">Size</span>
                            {item.size || defaultValue}
                        </p>
                        <p className="media-preview__field">
                            <span className="media-preview__field-label">Dimensions</span>
                            {item.width === 0 || item.height === 0 ? 'n/a' : `${item.width} x ${item.height}`}
                        </p>
                        <p className="media-preview__field">
                            <span className="media-preview__field-label">Date created</span>
                            {item.dateCreated ? format(item.dateCreated, 'D/M/YYYY HH:mm') : defaultValue}
                        </p>
                        <ul className="media-preview__info-actions">
                            {item.operations.map(operation => (
                                <li className={`media-action media-icon media-icon--${operation.key}`}>
                                    <a href={operation.url}>{operation.title}</a>
                                </li>
                            ))}
                            <li className="media-action media-icon media-icon--link">
                                <CopyToClipboard text={item.originalUrl} onCopy={this.onCopy}>
                                    <button>{isUrlCopied ? 'Copied!' : 'Copy url'}</button>
                                </CopyToClipboard>
                            </li>
                            <li className="media-action media-icon media-icon--download">
                                <a href={item.originalUrl} download target="_blank">Download original</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        );
    }
}

MediaPreview.propTypes = {
    setItemInPreview: PropTypes.func.isRequired,
    setVisible: PropTypes.func.isRequired,
    isVisible: PropTypes.bool.isRequired,
    // eslint-disable-next-line react/no-typos
    item: MediaItemPropType.isRequired,
    index: PropTypes.number.isRequired,
    total: PropTypes.number.isRequired,
};

export default MediaPreview;
