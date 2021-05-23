"use strict";
(()=>{

class TestComponent extends LayoutComponent {
    render() {
        return View(
            {},
            Slider(
                {
                    style: {
                        flex: 1
                    },
                    onPageChange: (page) => {
                        console.log(`onPageChange: ${page}`)
                    },
                    ref: ref => {
                        console.log("Slider ref " + ref)
                        this.slider = ref
                    }
                },
                View(
                    {
                        style: {
                            backgroundColor:"#3A414B",
                            backgroundImage: "https://img4.goodfon.ru/wallpaper/nbig/5/c6/google-nexus-nexus-5-linii-kraski.jpg",
                            backgroundResizeMode: "repeat",
                            backgroundBlurRadius: 25,
                            //backgroundColor: '#ff0000',
                            justifyContent: 'center',
                            alignItems: 'center',
                        }
                    },
                    Text({
                        style: {
                            color: '#ffffff',
                            fontSize: 24,
                            fontWeight: 'bold'
                        },
                        text: 'slide 1'
                    }),
                    Button({
                        text: 'next',
                        onClick: () => this.slider.nextSlide()
                    }),
                    Loader({
                        // style: {
                        //     width: 100,
                        //     height: 100
                        // },
                        tintColor: '#ffff00',
                        animating: true,
                        //size: 'large'
                    })
                ),
                View(
                    {
                        style: {
                            backgroundColor: '#00ff00',
                            justifyContent: 'center',
                            alignItems: 'center',
                        },
                    },
                    Text({
                        style: {
                            color: '#ffffff',
                            fontSize: 24,
                            fontWeight: 'bold'
                        },
                        text: 'slide 2'
                    }),
                    TextInput({
                        style: {
                            width: 100,
                            height: 50,
                        },
                        //keyboardType: 'visible-password',
                        secureTextEntry: true,
                        onChangeText: text => {
                            this.page = parseInt(text)
                            console.log("onChangeText page " + this.page)
                        }
                    }),
                    Button({
                        text: 'to page',
                        onClick: () => this.slider.scrollToPage(this.page)
                    })
                ),
                View(
                    {
                        style: {
                            backgroundColor: '#0000ff',
                            justifyContent: 'center',
                            alignItems: 'center',
                        },
                    },
                    Text({
                        style: {
                            color: '#ffffff',
                            fontSize: 24,
                            fontWeight: 'bold'
                        },
                        text: 'slide 3'
                    }),
                    Button({
                        text: 'prev',
                        onClick: () => this.slider.prevSlide()
                    })
                )
            )
        )
    }
}

var colors = {
    titleColor: '#333333',
    textColor: '#000000',
    linkColor: '#1d54a2',
    dateColor: '#acb2b9',
    separatorColor: '#e8e7e8',
    lineColor: '#d4d4d5',
    leftLineColor: '#92b0ee',
    backgroundColor: '#ffffff'
}

var notificationsDummy = [
    {
        type: '1',
        author: 'Павел Иохин',
        avatarUrl: 'https://cp.bitrix.ru/upload/resize_cache/main/570/212_212_1/tmp.png',
        text: `Напоминание о событии Отгул, 26.02.2020 отмеченного в Вашем календаре "Павел Иохин"<br/><b><font color="${colors.linkColor}">Посмотреть подробности</font></b>`,
        time: '01:45, 26 Февраля 2020'
    },
    {
        type: '1',
        author: 'Павел Юрин',
        avatarUrl: 'https://cp.bitrix.ru/upload/resize_cache/main/f2a/212_212_1/P1066310.png',
        text: 'Мне нравится ваше сообщение "есть" в чате "Работа в выходные - калининградский офис"',
        time: '10:51, 24 Февраля 2020'
    },
    {
        type: '2',
        author: 'Валерия Котанова',
        avatarUrl: 'https://cp.bitrix.ru/upload/resize_cache/main/6d3/212_212_1/%D1%84%D0%BE%D1%82%D0%BE%20%D0%B4%D0%BB%D1%8F%20%D0%B1%D0%B8%D1%82.png',
        text: 'Тикет №80108',
        subData: {
            title: 'Уведомления Mantis',
            icon: 'https://framalibre.org/sites/default/files/leslogos/mantis_logo_for_google_400x400.png',
            subtitle1: 'Открыть Mantis',
            subtitle2: 'Открыть Mantis из внешней сети',
            properties: [
                {
                    name: 'Проект',
                    value: 'Features',
                    direction: 'row'
                },
                {
                    name: 'Категория',
                    value: 'mobile',
                    direction: 'row'
                },
                {
                    name: 'Добавлено',
                    value: 'Теплякова Ольга',
                    direction: 'row'
                },
                {
                    name: 'Назначено',
                    value: 'Петриченко Евгений',
                    direction: 'row'
                },
                {
                    name: 'Статус',
                    value: 'новый',
                    direction: 'row'
                },
                {
                    name: 'Критичность',
                    value: 'нормальный',
                    direction: 'row'
                },
                {
                    name: 'Сводка',
                    value: 'б24: возможность открыть файл и просмотреть в событии календаря в мобильном приложеии',
                    direction: 'col'
                },
                {
                    separator: true
                },
                {
                    name: 'Тикеты',
                    value: '10-й тикет: imol|1437841',
                    direction: 'row'
                },
                {
                    name: 'Следить',
                    value: 'Котанова Валерия',
                    direction: 'row'
                },
            ]
        },
        time: 'Вчера, 23:00'
    },
    {
        type: '1',
        author: 'Евгений Шеленков',
        avatarUrl: 'https://cp.bitrix.ru/upload/resize_cache/main/593/212_212_1/btr_1759.png',
        text: 'Мне нравится ваше сообщение "В пн 24-го на 5м кто-то будет?" в чате "Работа в выходные - калининградский офис"',
        time: '15:29, 21 Февраля 2020'
    },
    {
        type: '2',
        author: 'Котанова Валерия',
        avatarUrl: 'https://cp.bitrix.ru/upload/resize_cache/main/6d3/212_212_1/%D1%84%D0%BE%D1%82%D0%BE%20%D0%B4%D0%BB%D1%8F%20%D0%B1%D0%B8%D1%82.png',
        text: 'Тикет №80109',
        subData: {
            title: 'Уведомления Mantis',
            icon: 'https://framalibre.org/sites/default/files/leslogos/mantis_logo_for_google_400x400.png',
            subtitle1: 'Открыть Mantis',
            subtitle2: 'Открыть Mantis из внешней сети',
            properties: [
                {
                    name: 'Проект',
                    value: 'Features',
                    direction: 'row'
                },
                {
                    name: 'Категория',
                    value: 'mobile',
                    direction: 'row'
                },
                {
                    name: 'Добавлено',
                    value: 'Ольга Теплякова',
                    direction: 'row'
                },
                {
                    name: 'Назначено',
                    value: 'Евгений Петриченко',
                    direction: 'row'
                },
                {
                    name: 'Статус',
                    value: 'old',
                    direction: 'row'
                },
                {
                    name: 'Критичность',
                    value: 'critical',
                    direction: 'row'
                },
                {
                    name: 'Сводка',
                    value: 'б25: возможность открыть файл и просмотреть в событии календаря в мобильном приложеии',
                    direction: 'col'
                },
                {
                    separator: true
                },
                {
                    name: 'Тикеты',
                    value: '11-й тикет: imol|1437841',
                    direction: 'row'
                },
                {
                    name: 'Следить',
                    value: 'Валерия Котанова',
                    direction: 'row'
                },
            ]
        },
        time: 'Вчера, 23:30'
    },
]

var notifications = [
    ...notificationsDummy,
    ...notificationsDummy,
    ...notificationsDummy,
    ...notificationsDummy,
    ...notificationsDummy,
    ...notificationsDummy,
    ...notificationsDummy,
    ...notificationsDummy,
    ...notificationsDummy,
    ...notificationsDummy,
    ...notificationsDummy,
    ...notificationsDummy,
    ...notificationsDummy,
    ...notificationsDummy,
    ...notificationsDummy,
    ...notificationsDummy
]

var styles = {
    contentWrapper: {
        flexDirection: 'row',
        backgroundColor: colors.backgroundColor,
        padding: 11,
        paddingBottom: 22,
        borderBottomWidth: 1,
        borderBottomColor: colors.separatorColor
    },
    avatar: {
        width: 35,
        height: 35,
        marginRight: 10,
        borderRadius: 17
    },
    content: {
        width: 150,
        flexGrow: 1
    },
    author: {
        color: colors.titleColor,
        fontWeight: 'bold',
        fontSize: 14,
        marginBottom: 6
    },
    text: {
        color: colors.textColor,
        fontSize: 14,
    },
    time: {
        color: colors.dateColor,
        fontSize: 12,
        marginTop: 6
    },
    closeButton: {
        color: colors.dateColor,
        width: 20,
        height: 20
    },
    table: {
        marginTop: 5,
        paddingLeft: 10,
        borderLeftWidth: 2,
        borderLeftColor: colors.leftLineColor
    },
    tableHeader: {
        flexDirection: 'row'
    },
    tableHeaderIcon: {
        width: 30,
        height: 30,
        marginRight: 10,
        borderRadius: 15
    },
    tableHeaderTitle: {
        color: colors.titleColor,
        fontWeight: 'bold',
        fontSize: 14,
        marginBottom: 5
    },
    tableHeaderSubTitle: {
        color: colors.linkColor,
        fontWeight: 'bold',
        fontSize: 12,
        marginTop: 2
    },
    tableSeparator: {
        height: 1,
        backgroundColor: colors.lineColor,
        marginTop: 5
    },
    tableItem: (direction) => ({
        flexDirection: direction === 'row' ? 'row': 'column',
        marginTop: 5,
    }),
    tableItemKey: {
        color: colors.textColor,
        fontWeight: 'bold',
        fontSize: 12,
        width: 100
    },
    tableItemValue: {
        color: colors.textColor,
        fontSize: 12,
    },
    listView: {
        backgroundColor: colors.backgroundColor
    }
}

class NotificationItem extends LayoutComponent {
    constructor(props) {
        super(props)

        this.state = {
            flag: false
        }
    }

    render() {
        return View(
            {
                style: styles.contentWrapper,
                onClick: () => {
                    console.log(`notification ${JSON.stringify(this.props.notification)}`)
                }
            },
            Image({
                uri: this.props.notification.avatarUrl,
                resizeMode: 'cover',
                style: styles.avatar
            }),
            View(
                { style: styles.content },
                Text({
                    text: this.state.flag ? ">(0)_(0)<" : this.props.notification.author,
                    style: styles.author
                }),
                Text({
                    html: this.props.notification.text,
                    style: styles.text
                }),
                this.props.notification.subData && SubDataElement({ data: this.props.notification.subData }),
                Text({
                    text: this.props.notification.time,
                    style: styles.time
                })
            ),
            Button({
                text: 'X',
                style: styles.closeButton,
                onClick: () => {
                    this.setState({
                        flag: !this.state.flag
                    })
                }
            })
        )
    }
}

var NotificationItemElement = (props) => View(
    {
        style: styles.contentWrapper,
        onClick: () => {
            console.log(`notification ${JSON.stringify(props.notification)}`)
        }
    },
    Image({
        uri: props.notification.avatarUrl,
        resizeMode: 'cover',
        style: styles.avatar
    }),
    View(
        { style: styles.content },
        Text({
            text: props.notification.author,
            style: styles.author
        }),
        Text({
            html: props.notification.text,
            style: styles.text
        }),
        props.notification.subData && SubDataElement({ data: props.notification.subData }),
        Text({
            text: props.notification.time,
            style: styles.time
        })
    ),
    Button({
        text: 'X',
        style: styles.closeButton,
        onClick: () => {

        }
    })
)

var TableHeader = (data) => View(
    { style: styles.tableHeader },
    Image({
        uri: data.icon,
        resizeMode: 'cover',
        style: styles.tableHeaderIcon
    }),
    Text({
        text: data.title,
        style: styles.tableHeaderTitle
    })
)

var TableSeparator = () => View({
    style: styles.tableSeparator
})

var TableItem = (property) => View(
    { style: styles.tableItem(property.direction) },
    Text({
        text: property.name,
        style: styles.tableItemKey
    }),
    Text({
        text: property.value,
        style: styles.tableItemValue
    })
)

var SubDataElement = ({ data }) => View(
    { style: styles.table },
    TableHeader(data),
    Text({
        text: data.subtitle1,
        style: styles.tableHeaderSubTitle
    }),
    Text({
        text: data.subtitle2,
        style: styles.tableHeaderSubTitle
    }),
    TableSeparator(),
    ...data.properties.map(property => property.separator
        ? TableSeparator()
        : TableItem(property)
    )
)

class NotificationsComponent extends LayoutComponent {
    constructor(props) {
        super(props)

        this.state = {
            notifications: props.notifications,
            isRefreshing: false,
        }
    }

    render() {
        const { notifications, isRefreshing } = this.state
        return ListView({
            style: styles.listView,
            data: [{
                items: notifications
            }],
            isRefreshing: isRefreshing,
            //renderItem: (data) => NotificationItemElement({ notification: data }),
            renderItem: (data) => new NotificationItem({ notification: data }),
            onRefresh: () => {
                this.setState(() => ({ isRefreshing: true }), () => {
                    this.setState({ isRefreshing: false })
                })
                // setTimeout(() => {
                //     this.setState({ isRefreshing: false })
                // }, 5000)
            },
            onLoadMore: () => {
                setTimeout(() => {
                    const { notifications } = this.state

                    this.setState({ notifications: [
                        ...notifications,
                        ...notificationsDummy
                    ] })
                }, 1000)
            }
        })
    }
}

const notificationsComponent = new NotificationsComponent({ notifications: notifications })

class InnerComponent2 extends LayoutComponent {
    constructor(props) {
        super(props)

        this.state = {
            flag: false
        }
    }

    printState() {
        console.log("InnerComponent2 state")
        console.log(this.state)
    }

    render() {
        return View(
            {
                style: {
                    backgroundColor: this.state.flag ? '#00ffff' : '#ffffff',
                    width: '50%',
                    height: '50%',
                },
                onClick: () => {
                    this.setState((state) => ({ flag: !state.flag }))
                }
            }
        )
    }
}

class InnerComponent extends LayoutComponent {
    constructor(props) {
        super(props)

        this.state = {
            flag: props.flag
        }
    }

    componentWillReceiveProps(nextProps) {
        this.setState({
            flag: nextProps.flag
        })
    }

    printState() {
        console.log("InnerComponent state")
        console.log(this.state)
    }

    render() {
        return View(
            {
                style: {
                    backgroundColor: this.props.flag ? '#0000ff' : '#ffff00',
                    width: '50%',
                    height: '50%',
                    justifyContent: 'center',
                    alignItems: 'center'
                },
                onClick: () => {
                    this.setState((state) => ({ flag: !state.flag }))
                },
                ref: ref => {
                    console.log("InnerComponent Root " + ref)
                    ref.print()
                }
            },
            new InnerComponent2({
                ref: ref => {
                    console.log("InnerComponent InnerComponent2 " + ref)
                    ref.printState()
                }
            })
        )
    }
}

class OuterComponent extends LayoutComponent {
    constructor(props) {
        super(props)

        this.state = {
            flag: false,
            showImage: false,
            replaceImage: false,
            replaceComponent: false,
        }
    }

    componentWillUnmount() {
        console.log(this.stateChangeListener)
    }

    printState() {
        console.log("OuterComponent state")
        console.log(this.state)
    }

    render() {
        return View(
            {
                style: {
                    backgroundColor: this.state.flag ? '#ff0000' : '#00ff00',
                    justifyContent: 'center',
                    alignItems: 'center'
                },
                onClick: () => {
                    this.setState((state) => ({ flag: !state.flag }))
                }
            },
            this.state.showImage && Image({
                uri: 'https://img4.goodfon.ru/wallpaper/nbig/5/c6/google-nexus-nexus-5-linii-kraski.jpg',
                style: {
                    width: 100,
                    height: 100
                }
            }),
            this.state.replaceImage ? Image({
                uri: 'https://img4.goodfon.ru/wallpaper/nbig/5/c6/google-nexus-nexus-5-linii-kraski.jpg',
                style: {
                    width: 100,
                    height: 100
                }
            }) : (
                this.state.replaceComponent ? new InnerComponent2({ flag: this.state.flag, ref: ref => {
                    console.log("InnerComponent2 " + ref)
                    ref.printState()
                } }) : new InnerComponent({ flag: this.state.flag, ref: ref => {
                    console.log("InnerComponent " + ref)
                    ref.printState()
                } })
            ),
            View(
                {
                    style: {
                        flexDirection: 'row'
                    }
                },
                Button({
                    text: 'show/hide image',
                    onClick: () => {
                        this.setState((state) => ({
                            showImage: !state.showImage
                        }))
                    }
                }),
                Button({
                    text: 'replace image',
                    onClick: () => {
                        this.setState((state) => ({
                            replaceImage: !state.replaceImage
                        }))
                    }
                }),
                Button({
                    text: 'replace component',
                    onClick: () => {
                        this.setState((state) => ({
                            replaceComponent: !state.replaceComponent
                        }))
                    }
                })
            )
        )
    }
}

let outerComponent = new OuterComponent({
    ref: (ref) => {
        console.log("OuterComponent " + ref);
        ref.printState()
    }
})

class DemoComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				receivedAmount: '0',
			};
		}

		render()
		{
            return View(
				{},
				TextInput(
					{

                        // value: this.state.receivedAmount,
                        forcedValue: this.state.receivedAmount,
						style: {
							color: '#333333',
							fontSize: 20,
							fontWeight: 'bold',
							margin: 10,
							backgroundColor: '#00000000',
						},
						onChangeText: (text) => {
                            console.log("onChangeText " + text)
							this.setState({
								receivedAmount: text.replace(/[^0-9]/g, ''),
							});
						},
					}
				)
            );
		}
	}

BX.onViewLoaded(() => {
    layoutWidget.showComponent(new DemoComponent())
})

})();
