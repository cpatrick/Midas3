add_subdirectory( controllers )

add_midas_style_test( StyleTestsHelloworldControllers
  ${CMAKE_SOURCE_DIR}/modules/kiwi/tests/controllers
)
